<?php

namespace bigland\queue\chain\storage;

use bigland\queue\chain\models\JobResult;
use Yii;
use yii\base\BaseObject;
use yii\di\Instance;
use yii\redis\Connection;
use yii\redis\Mutex;

/**
 * Redis Storage
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class RedisStorage extends BaseObject implements StorageInterface
{
    public Connection|array|string $redis = 'redis';

    public Mutex|string|array|null $mutex = null;

    public string $prefix = 'queue-chain';


    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->redis = Instance::ensure($this->redis, Connection::class);

        $this->mutex = match (true) {
            is_string($this->mutex) => Instance::ensure($this->mutex, Mutex::class),
            is_array($this->mutex) => Yii::createObject($this->mutex),
            is_object($this->mutex) => $this->mutex,
            default => null
        };
    }


    public function addPushedCount(string $groupId, int $jobId)
    {
        $this->redis->incr("$this->prefix.$groupId.pushed");
    }


    public function pushJobResult(JobResult $jobResult): array
    {
        $groupId = $jobResult->groupId;

        while (!$this->acquire()) {
            usleep(250000);
        }

        $doneCount = $this->redis->incr("$this->prefix.$groupId.done");
        $totalCount = (int)$this->redis->get("$this->prefix.$groupId.pushed");

        $this->release();

        $this->redis->rpush("$this->prefix.$groupId.results", serialize($jobResult));

        return [(int) $doneCount, (int) $totalCount];
    }


    public function getProgress(string $groupId): array
    {
        while (!$this->acquire()) {
            usleep(250000);
        }

        $data = [
            (int)$this->redis->get("$this->prefix.$groupId.done"),
            (int)$this->redis->get("$this->prefix.$groupId.pushed"),
        ];

        $this->release();

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function reset(string $groupId): array
    {
        $results = $this->getGroupResult($groupId);

        $this->redis->del(
            "$this->prefix.$groupId.results",
            "$this->prefix.$groupId.done",
            "$this->prefix.$groupId.pushed"
        );

        return $results;
    }


    public function getGroupResult(string $groupId): array
    {
        $results = [];

        while (($result = $this->redis->lpop("$this->prefix.$groupId.results")) !== null) {
            $results[] = unserialize($result);
        }

        return $results;
    }


    protected function acquire(): bool
    {
        return $this->mutex ? $this->mutex->acquire("$this->prefix.lock") : true;
    }


    protected function release(): bool
    {
        return $this->mutex ? $this->mutex->release("$this->prefix.lock") : true;
    }
}
