<?php

namespace App\Message;

abstract class ProjectAnalyseAbstract
{
    private int $projectId;

    public function __construct(
        int $projectId,
    ) {
        $this->projectId = $projectId;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }
}