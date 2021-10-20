<?php

namespace bigland\queue\chain\models;

use Stringable;

class JobResult implements Stringable
{
    public function __construct(public int $id, public string $groupId, public mixed $result) {}

    public function __toString(): string
    {
        return $this->id;
    }
}