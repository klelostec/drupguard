<?php

namespace App\Plugin\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

abstract class Plugin
{
    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
}
