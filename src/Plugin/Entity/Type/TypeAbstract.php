<?php

namespace App\Plugin\Entity\Type;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class TypeAbstract implements TypeInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    abstract public function validate(ExecutionContextInterface $context): void;
}
