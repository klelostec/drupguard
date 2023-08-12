<?php

namespace App\Form\Type;

use App\Entity\Install;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class DbDriverType extends AbstractType {

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @required
     */
    public function setTranslator(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $defaultOptions = [
            'empty_data' => Install::getDbDriversDefault()
        ];

        $defaultOptions['choices'] = function (Options $options) {
            $choices = [];

            foreach (Install::getDbDrivers() as $label => $value) {
                $choices[$this->translator->trans($label, [], 'form')] = $value;
            }

            return $choices;
        };

        $resolver->setDefaults($defaultOptions);
    }

    /**
     * {@inheritDoc}
     */
    public function getParent() : ?string {
        return ChoiceType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix() : string {
        return 'form_type_dbDriver';
    }

}
