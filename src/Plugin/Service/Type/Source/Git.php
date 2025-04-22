<?php

namespace App\Plugin\Service\Type\Source;

use App\Entity\Plugin\Type\Source\Git as GitEntity;
use App\Entity\Project;
use App\Form\Plugin\Type\Source\Git as GitForm;
use App\Plugin\Annotation\TypeInfo;
use App\Plugin\Exception\Source as SourceException;
use App\Plugin\Service\Source;
use App\Repository\Plugin\Type\Source\Git as GitRepository;
use CzProject\GitPhp\Git as GitClient;
use Symfony\Component\Filesystem\Filesystem;

#[TypeInfo(
    id: 'git',
    name: 'Git',
    type: 'source',
    entityClass: GitEntity::class,
    repositoryClass: GitRepository::class,
    formClass: GitForm::class
)]
class Git extends Source
{
    public function source(Project $project, mixed $source): string
    {
        /**
         * @var GitEntity $source
         */
        $path = $this->getPath($project, $source);
        $fileSystem = new Filesystem();

        $dirExists = $fileSystem->exists($path);
        $gitClient = new GitClient();
        try {
            if ($dirExists && $fileSystem->exists($path.'/.git')) {
                $repo = $gitClient->open($path);
                $repo->fetch(null, ['--all', '-p']);
                if ($repo->hasChanges()) {
                    $repo->run('reset', '--hard', 'origin/'.$source->getBranch());
                    $repo->run('clean', '-fd');
                    $repo->run('checkout', '.');
                }
                if ($repo->getCurrentBranchName() !== $source->getBranch()) {
                    $repo->checkout($source->getBranch());
                } else {
                    $repo->pull();
                }
            } else {
                if ($dirExists) {
                    $fileSystem->remove($path);
                }
                $fileSystem->mkdir($path);
                $repo = $gitClient->cloneRepository($source->getRepository(), $path);
                $repo->checkout($source->getBranch());
            }
        } catch (\Exception $e) {
            throw new SourceException($this->translator->trans('Error during git operations.'), $e);
        }

        return $path;
    }
}
