<?php

namespace App\Service;

class MachineNameHelper
{

    public function getMachineName(string $string):string {
        $string = mb_strtolower($string);
        $string = preg_replace('@[^a-z0-9_.]+@', '_', $string);
        return $string;
    }

}