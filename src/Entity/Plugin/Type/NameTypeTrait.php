<?php

namespace App\Entity\Plugin\Type;

use Doctrine\ORM\Mapping as ORM;

trait NameTypeTrait
{
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
