<?php

namespace App\Entity\Plugin\Type\Build;

use App\Entity\Plugin\Type\PathTypeAbstract;
use App\Form\Plugin\Type\Build\Composer as ComposerForm;
use App\Plugin\TypeInfo;
use App\Repository\Plugin\Type\Build\Composer as ComposerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Table(name: 'build_composer')]
#[ORM\Entity(repositoryClass: ComposerRepository::class)]
#[TypeInfo(id: 'composer', name: 'Composer', type: 'build', entityClass: Composer::class, repositoryClass: ComposerRepository::class, formClass: ComposerForm::class, dependencies: [
    'source'=> '*'
])]
class Composer extends PathTypeAbstract
{
    #[ORM\Column(length: 255)]
    #[Assert\Choice(callback: 'getVersions')]
    #[Assert\NotBlank()]
    protected ?string $version = null;

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;
        return $this;
    }

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context): void
    {
        parent::validate($context);
    }

    public static function getVersions(): array
    {
        return [
            'Version 2' => 'v2',
            'Version 1' => 'v1',
        ];
    }

    public function __toString()
    {
        $version = $this->getVersion() ? ' - ' . array_flip(static::getVersions())[$this->version] : '';
        return 'Composer' . $version.parent::__toString();
    }
}
