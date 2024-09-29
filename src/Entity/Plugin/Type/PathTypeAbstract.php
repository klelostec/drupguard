<?php

namespace App\Entity\Plugin\Type;

use Doctrine\ORM\Mapping as ORM;

abstract class PathTypeAbstract extends TypeAbstract
{
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $path = null;

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function __toString()
    {
        return !empty($this->getPath()) ? ' - '.$this->getPath() : '';
    }
}
