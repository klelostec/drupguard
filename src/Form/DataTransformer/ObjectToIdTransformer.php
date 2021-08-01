<?php

namespace App\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ObjectToIdTransformer implements DataTransformerInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var string
     */
    private $class;

    public function __construct(ManagerRegistry $registry, string $class, bool $multiple)
    {
        $this->registry = $registry;
        $this->class = $class;
        $this->multiple = $multiple;
    }

    /**
     * Transforms an object (object) to a string (id).
     *
     * @param array|object|null $object
     */
    public function transform($object): array
    {
        if (null === $object) {
            return $this->multiple ? [] : ['id' => '', 'label' => ''];
        }

        if ($this->multiple) {
            $ret = [];
            foreach ($object as $obj) {
                $ret[] = ['id' => $obj->getId(), 'label' => (string) $obj];
            }
            return $ret;
        } else {
            return ['id' => $object->getId(), 'label' => (string) $object];
        }
    }

    /**
     * Transforms a string (id) to an object (object).
     *
     * @param array|string|int|null $id
     *
     * @throws TransformationFailedException if object (object) is not found
     */
    public function reverseTransform($id)
    {
        if (empty($id)) {
            return $this->multiple ? [] : null;
        }

        if ($this->multiple) {
            $ret = [];
            foreach ($id as $i) {
                $object = $this->registry->getManagerForClass($this->class)->getRepository($this->class)->find($i);
                if (null !== $object) {
                    $ret[] = $object;
                }
            }
            return new ArrayCollection($ret);
        } else {
            $object = $this->registry->getManagerForClass($this->class)->getRepository($this->class)->find($id);
            if (null === $object) {
                $msg = 'Object from class %s with id "%s" not found';
                throw new TransformationFailedException(\sprintf($msg, $this->class, $id));
            }

            return $object;
        }
    }
}
