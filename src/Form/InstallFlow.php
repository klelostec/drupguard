<?php

namespace App\Form;

use Craue\FormFlowBundle\Form\FormFlow;

class InstallFlow extends FormFlow {

    /**
     * {@inheritDoc}
     */
//    protected $allowRedirectAfterSubmit = true;

    /**
     * {@inheritDoc}
     */
    protected $allowDynamicStepNavigation = true;

    /**
     * {@inheritDoc}
     */
    protected function loadStepsConfig():array {
        $formType = InstallForm::class;

        return [
            [
                'label' => 'Requirements',
                'form_type' => $formType,
            ],
            [
                'label' => 'Database',
                'form_type' => $formType,
            ],
            [
                'label' => 'Email',
                'form_type' => $formType,
            ],
            [
                'label' => 'Confirmation',
            ],
        ];
    }

}