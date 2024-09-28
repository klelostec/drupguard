<?php

namespace App\Plugin\Exception;

class PluginNotFound extends \Exception {

    function __construct(string $id)
    {
        $message = 'Plugin "' . $id . '" not found';
        parent::__construct($message);
    }
}