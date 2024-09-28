<?php

namespace App\Entity\Plugin\Type\Source;

use App\Entity\Plugin\Type\PathTypeAbstract;
use App\Form\Plugin\Type\Source\Local as LocalForm;
use App\Plugin\TypeInfo;
use App\Repository\Plugin\Type\Source\Local as LocalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Table(name: 'source_local')]
#[ORM\Entity(repositoryClass: LocalRepository::class)]
#[TypeInfo(id: 'local', name: 'Local', type: 'source', entityClass: Local::class, repositoryClass: LocalRepository::class, formClass: LocalForm::class)]
class Local extends PathTypeAbstract
{
    protected bool $checkPathOnFileSystem = true;

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context): void
    {
        $path = $this->getPath();
        if (empty($path)) {
            $context
                ->getValidator()
                ->inContext($context)
                ->atPath('path')
                ->validate($path, new Assert\NotBlank())
            ;

            return;
        }
        parent::validate($context);
    }

    public function __toString()
    {
        return 'Local'.parent::__toString();
    }
}
