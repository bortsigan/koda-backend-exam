<?php

namespace App\Exceptions;

use Exception;

class ProjectNotFoundException extends Exception
{
    public function __construct(int $id)
    {
        parent::__construct("Project with id {$id} not found.");
    }
}
