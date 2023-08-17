<?php

namespace App\Exception;

class InstallException extends \Exception
{
    protected string $redirect;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getRedirect(): string
    {
        return $this->redirect;
    }

    /**
     * @param string $redirect
     * @return InstallException
     */
    public function setRedirect(string $redirect): InstallException
    {
        $this->redirect = $redirect;
        return $this;
    }
}