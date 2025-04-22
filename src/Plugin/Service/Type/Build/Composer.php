<?php

namespace App\Plugin\Service\Type\Build;

use App\Entity\Plugin\Type\Build\Composer as ComposerEntity;
use App\Entity\Project;
use App\Form\Plugin\Type\Build\Composer as ComposerForm;
use App\Plugin\Annotation\TypeInfo;
use App\Plugin\Exception\Build as BuildException;
use App\Plugin\Service\Build;
use App\Repository\Plugin\Type\Build\Composer as ComposerRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[TypeInfo(
    id: 'composer',
    name: 'Composer',
    type: 'build',
    entityClass: ComposerEntity::class,
    repositoryClass: ComposerRepository::class,
    formClass: ComposerForm::class,
    dependencies: [
        'source' => '*',
    ]
)]
class Composer extends Build
{
    public function build(Project $project, mixed $build, string $path)
    {
        $fileSystem = new Filesystem();

        /**
         * @var ComposerEntity $build
         */
        if (!empty($build->getPath())) {
            $path .= $build->getPath();
        }

        if (!$fileSystem->exists($path.'/composer.lock')) {
            throw new BuildException($this->translator->trans('Composer files not found.'));
        }

        $composerBinary = 'composer';
        switch ($build->getVersion()) {
            case 'v1':
                $composerBinary = 'composer';
                break;
            case 'v2':
            default:
                break;
        }

        $composerCmd = explode(
            ' ',
            $composerBinary.' install --ignore-platform-reqs --no-scripts --no-plugins --no-cache --no-autoloader --quiet --no-interaction'
        );
        $composerInstall = new Process($composerCmd, $path);
        $composerInstall->setTimeout(60 * 60);
        if (0 !== $composerInstall->run()) {
            $e = new \Exception($composerInstall->getErrorOutput());
            throw new BuildException($this->translator->trans('Composer install failed.'), $e);
        }
    }
}
