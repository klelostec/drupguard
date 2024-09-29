<?php

namespace App\Entity\Plugin\Type\Source;

use App\Entity\Plugin\Type\TypeAbstract;
use App\Form\Plugin\Type\Source\Git as GitForm;
use App\Plugin\TypeInfo;
use App\Repository\Plugin\Type\Source\Git as GitRepository;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'source_git')]
#[ORM\Entity(repositoryClass: GitRepository::class)]
#[TypeInfo(id: 'git', name: 'Git', type: 'source', entityClass: Git::class, repositoryClass: GitRepository::class, formClass: GitForm::class)]
#[AppAssert\Plugin\Git()]
class Git extends TypeAbstract
{
    #[ORM\Column(length: 255)]
    protected ?string $repository = null;

    #[ORM\Column(length: 255)]
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

    public function __toString()
    {
        return 'Git'.
            $this->repository && $this->branch ?
                ' - '.$this->repository.' - '.$this->branch :
                '';
    }
}
