<?php

namespace bigland\queue\chain\models;

use Stringable;

class GroupPushedResult implements Stringable
{
    public function __construct(public string $groupId, public array $jobIds) {}

    public function __toString(): string
    {
        return $this->groupId;
    }
}