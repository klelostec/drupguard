<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class SourceSettingsAbstract implements SourceSettingsInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    abstract public function validate(ExecutionContextInterface $context): void;

    public function __toString()
    {
        return '';
    }
}
