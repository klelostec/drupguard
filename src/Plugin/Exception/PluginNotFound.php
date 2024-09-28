<?php

namespace App\Plugin\Exception;

class PluginNotFound extends \Exception
{
    public function __construct(string $id)
    {
        $message = 'Plugin "'.$id.'" not found';
        parent::__construct($message);
    }
}
