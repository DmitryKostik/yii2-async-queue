<?php

namespace bigland\queue\chain\behavior;

use bigland\queue\chain\job\ChainJobInterface;
use bigland\queue\chain\models\GroupPushedResult;
use bigland\queue\chain\models\JobResult;
use bigland\queue\chain\storage\StorageInterface;
use yii\base\Behavior;
use yii\base\InvalidArgumentException;
use yii\di\Instance;
use yii\queue\ExecEvent;
use yii\queue\PushEvent;
use yii\queue\Queue;

/**
 * Chain Behavior
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class ChainBehavior extends Behavior
{
    /**
     * @var StorageInterface|array|string
     */
    public $storage;

    /**
     * @var Queue
     * @inheritdoc
     */
    public $owner;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->storage = Instance::ensure($this->storage, StorageInterface::class);
    }

    /**
     * Возвращает прогресс выполнения группы заданий
     *
     * @param string $groupId
     *
     * @return array
     */
    public function getGroupProgress($groupId)
    {
        return $this->storage->getProgress($groupId);
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Queue::EVENT_AFTER_PUSH  => 'afterPush',
            Queue::EVENT_AFTER_EXEC  => 'afterExec',
            Queue::EVENT_AFTER_ERROR => 'afterError',
        ];
    }


    public function afterPush(PushEvent $event)
    {
        if (!$event->job instanceof ChainJobInterface) {
            return;
        }
        $this->storage->addPushedCount($event->job->getGroupId(), $event->id);
    }


    public function afterExec(ExecEvent $event)
    {
        ($event->job instanceof ChainJobInterface) && $this->registerResult($event);
    }


    public function afterError(ExecEvent $event)
    {
        if (!$event->job instanceof ChainJobInterface) {
            return;
        }

        if ($event->retry) {
            return;
        }

        $this->registerResult($event);
    }

    /**
     * @param ExecEvent $event
     */
    protected function registerResult(ExecEvent $event)
    {
        $groupId = $event->job->getGroupId();
        $result = new JobResult($event->id, $groupId, $event->result);

        list($pos, $size) = $this->storage->pushJobResult($result);

        if ($size > 0 && $pos == $size) {
            $results = $this->storage->getGroupResult($groupId);
            $event->job->finalizeGroup($this->owner, $results);
        }
    }


    public function pushGroup(array $jobs): GroupPushedResult
    {
        $this->validateGroup($jobs) or throw new InvalidArgumentException('One or more jobs not implement ' . ChainJobInterface::class);

        $groupId = \Yii::$app->security->generateRandomString();

        return new GroupPushedResult($groupId, $this->pushJobs($groupId, $jobs));
    }


    protected function pushJobs(string $groupId, array $jobs): array
    {
        return array_map(fn(ChainJobInterface $job) => $this->owner->push($job->setGroupId($groupId)), $jobs);
    }


    protected function validateGroup(array $jobs): bool
    {
        return array_reduce($jobs, fn(bool $isValid, $job) => $isValid && ($job instanceof ChainJobInterface), true);
    }
}
