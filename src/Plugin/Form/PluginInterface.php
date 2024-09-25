<?php

namespace App\Plugin\Form;

use App\Plugin\Manager;

interface PluginInterface
{
    public function getPluginManager(): Manager;
}
