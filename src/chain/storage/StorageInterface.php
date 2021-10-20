<?php

namespace bigland\queue\chain\storage;

use bigland\queue\chain\models\JobResult;

/**
 * Chain Storage Interface
 *
 * Интерфейс хранилища данных о выполнении групп заданий.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface StorageInterface
{
    /**
     * Метод должен увеличивать на единицу счетчик поставленных в очередь заданий в рамках
     * конкретной группы.
     *
     * @param string $groupId
     * @param int    $jobId
     */
    public function addPushedCount(string $groupId, int $jobId);


    /**
     * Увеличивает счетчик кол-ва выполненных заданий и добавляет результат выполнения в список.
     *
     * @param JobResult $jobResult
     */
    public function pushJobResult(JobResult $jobResult): array;


    /**
     * Метод должен вырнуть прогресс выполнения группы заданий.
     *
     * @param string $groupId
     *
     * @return array
     */
    public function getProgress(string $groupId);


    /**
     * Сброс данных группы в хранилище.
     *
     * @param string $groupId
     *
     * @return array массив результатов выполнения всей группы заданий
     */
    public function reset(string $groupId);


    /**
     * Возвращает результат выполнениях всех заданий группы.
     * Удаляет результат из storage.
     *
     * @param string $groupId
     *
     * @return array
     */
    public function getGroupResult(string $groupId): array;
}
