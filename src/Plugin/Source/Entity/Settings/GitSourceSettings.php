<?php

namespace App\Plugin\Source\Entity\Settings;

use App\Plugin\Source\Repository\GitSourceSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: GitSourceSettingsRepository::class)]
class GitSourceSettings extends SourceSettingsAbstract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $repository = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $branch = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRepository(): ?string
    {
        return $this->repository;
    }

    public function setRepository(?string $repository): static
    {
        $this->repository = $repository;

        return $this;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }

    public function setBranch(?string $branch): static
    {
        $this->branch = $branch;

        return $this;
    }

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context): void
    {
        $test = TRUE;
    }
}
