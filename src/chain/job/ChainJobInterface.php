<?php

namespace bigland\queue\chain\job;

use yii\queue\JobInterface;
use yii\queue\Queue;

/**
 * Chain Job Group Interface
 */
interface ChainJobInterface extends JobInterface
{
    /**
     * Уникальный идентификатор группы заданий, по которому будет определяться сколько этих заданий
     * отправлено и сколько выполнено.
     * @return string
     */
    public function getGroupId(): string;


    /**
     * Устанавливает уникальный идентификатор группы заданий.
     *
     * @param string $value
     *
     * @return ChainJobInterface
     */
    public function setGroupId(string $value): static;

    /**
     * Метод будет запущен после того, как выполнится вся группа заданий.
     * @param Queue $queue
     * @param array $results результаты выполненных заданий.
     */
    public function finalizeGroup($queue, array $results);
}
