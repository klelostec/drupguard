<?php

namespace App\Plugin\Exception;

class PluginTypeException extends \Exception
{
    protected $originException;

    public function __construct(string $message, ?\Exception $originException = null)
    {
        parent::__construct($message);
        $this->originException = $originException;
    }

    public function getOriginException(): ?\Exception
    {
        return $this->originException;
    }
}
