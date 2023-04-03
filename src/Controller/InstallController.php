<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Contracts\Translation\TranslatorInterface;

class InstallController extends AbstractController
{
    /**
     * @var TranslatorInterface $translator
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function index(Request $request): Response
    {
        $step = 'db';
        $form = $this->getFormStep();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('articles_list');
        }

        // render the form if it is the first request or if the validation failed
        return $this->render('install/index.html.twig', [
            'steps' => $this->getSteps(),
            'current_step' => $step,
            'form' => $form->createView(),
        ]);
    }

    protected function getSteps() {
        return [
            'db' => $this->translator->trans('Database')
        ];
    }

    protected function getFormStep($step = NULL) {
        $form = $this->createFormBuilder();
        switch ($step) {
            default:
            case 'db':
                $form
                    ->add('task', TextType::class, [
                        'constraints' => new NotBlank(),
                    ])
                    ->add('dueDate', DateType::class, [
                        'constraints' => [
                            new NotBlank(),
                            new Type(\DateTime::class),
                        ],
                    ]);
                break;
        }
        return $form
            ->getForm();
    }
}
