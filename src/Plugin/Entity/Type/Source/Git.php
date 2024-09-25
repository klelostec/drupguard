<?php

namespace App\Plugin\Entity\Type\Source;

use App\Plugin\Entity\Type\TypeAbstract;
use App\Plugin\Repository\Type\Source\Git as GitRepository;
use CzProject\GitPhp\Git as GitClient;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Table(name: 'source_git')]
#[ORM\Entity(repositoryClass: GitRepository::class)]
class Git extends TypeAbstract
{
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    protected ?string $repository = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    protected ?string $branch = null;

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
        $git = new GitClient();
        if (!empty($this->repository) && !$git->isRemoteUrlReadable($this->repository, [])) {
            $context
                ->buildViolation('Cannot access to repository.')
                ->atPath('repository')
                ->addViolation();
        }
        if (!empty($this->repository) && !empty($this->branch) && !$git->isRemoteUrlReadable($this->repository, [$this->branch])) {
            $context
                ->buildViolation('Branch not founded.')
                ->atPath('branch')
                ->addViolation();
        }
    }

    public function __toString()
    {
        return
            $this->repository && $this->branch ?
                $this->repository.' - '.$this->branch :
                '';
    }
}
