<?php

namespace App\Entity\Plugin\Type\Analyse;

use App\Entity\Plugin\Type\NameTypeInterface;
use App\Entity\Plugin\Type\NameTypeTrait;
use App\Entity\Plugin\Type\PathTypeInterface;
use App\Entity\Plugin\Type\PathTypeTrait;
use App\Entity\Plugin\Type\TypeAbstract;

class DrupalAbstract extends TypeAbstract implements PathTypeInterface, NameTypeInterface
{
    use NameTypeTrait;
    use PathTypeTrait {
        PathTypeTrait::__toString as traitToString;
    }
}
