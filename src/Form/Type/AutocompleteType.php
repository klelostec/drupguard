<?php

namespace App\Form\Type;

use App\Form\DataTransformer\ObjectToIdTransformer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutocompleteType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param FormBuilderInterface<AbstractType> $builder
     * @param array<string, mixed>               $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = new ObjectToIdTransformer($this->registry, $options['class'], $options['multiple']);
        $builder->addViewTransformer($transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => false,
            'multiple' => false
        ]);
        $resolver->setRequired([
            'class',
            'url'
        ]);
        $resolver->setAllowedTypes('class', [
            'string',
        ]);
        $resolver->setAllowedTypes('url', [
            'string',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['multiple']) {
            $view->vars['full_name'] .= '[]';
        }

        $view->vars = array_replace($view->vars, [
            'multiple' => $options['multiple'],
            'url' => $options['url']
        ]);
    }
}
