<?php

namespace App\Service;

use Behat\Transliterator\Transliterator;

class MachineNameHelper
{
    public function getMachineName(string $string): string
    {
        $string = mb_strtolower($string);
        $string = Transliterator::transliterate($string, '_');
        $string = preg_replace('@[^a-z0-9_]+@', '_', $string);
        return $string;
    }
}
