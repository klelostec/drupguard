<?php

namespace App\Plugin\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class Plugin
{
    protected TranslatorInterface $translator;

    protected LoggerInterface $logger;

    protected KernelInterface $appKernel;

    public function __construct(
        TranslatorInterface $translator,
        LoggerInterface $logger,
        KernelInterface $appKernel
    ) {
        $this->translator = $translator;
        $this->logger = $logger;
        $this->appKernel = $appKernel;
    }
}
