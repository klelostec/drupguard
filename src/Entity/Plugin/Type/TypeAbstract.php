<?php

namespace App\Entity\Plugin\Type;

use Doctrine\ORM\Mapping as ORM;

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
}
