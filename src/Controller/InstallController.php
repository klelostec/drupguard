<?php

namespace App\Controller;

use App\Validator\Conditional;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\When;
use Symfony\Contracts\Translation\TranslatorInterface;

class InstallController extends AbstractController
{
    /**
     * @var TranslatorInterface $translator
     */
    protected $translator;

    /**
     * @var SessionInterface $session
     */
    protected $session;

    /**
     * @var string $maxStep
     */
    protected $maxStep;

    /**
     * @var string $currentStep
     */
    protected $currentStep;

    /**
     * @var string $nextStep
     */
    protected $nextStep;

    /**
     * @var string $prevStep
     */
    protected $prevStep;

    /**
     * @var array $steps
     */
    protected $steps;

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack) {
        $this->translator = $translator;
        $this->session = $requestStack->getSession();
    }

    protected function init(Request $request) {
        if (!$this->currentStep) {
            $this->steps = [
                'db' => [
                    'index' => 0,
                    'access' => FALSE,
                    'label' => $this->translator->trans('Database', [], 'form'),
                    'url' => $this->generateUrl('install_index', ['step' => 'db']),
                    'default_values' => ['driver' => 'mysql']
                ],
                'email' =>  [
                    'index' => 1,
                    'access' => FALSE,
                    'label' => $this->translator->trans('Email', [], 'form'),
                    'url' => $this->generateUrl('install_index', ['step' => 'email']),
                    'default_values' => ['driver' => 'smtp']
                ],
                'check2' =>  [
                    'index' => 2,
                    'access' => FALSE,
                    'label' => $this->translator->trans('Check2', [], 'form'),
                    'url' => $this->generateUrl('install_index', ['step' => 'check2'])
                ]
            ];

            $this->currentStep = $request->get('step');
            $stepsIndexes = array_keys($this->steps);
            $this->prevStep = $this->steps[$this->currentStep]['index'] > 0 ?
                $stepsIndexes[$this->steps[$this->currentStep]['index'] - 1] :
                NULL;
            $this->nextStep = $this->steps[$this->currentStep]['index'] < count($stepsIndexes) - 1 ?
                $stepsIndexes[$this->steps[$this->currentStep]['index'] + 1] :
                NULL;

            $max_step = $this->session->get('install_max_step_index', 0) + 1;
            foreach ($this->steps as $key => $value) {
                $this->steps[$key]['access'] = $this->steps[$key]['index'] <= $max_step;
            }
        }
    }

    public function index(Request $request): Response
    {
        $this->init($request);
        if (empty($this->steps[$this->currentStep])) {
            $message = 'Parameter "{parameter}" for route "{route}" must match "{expected}" ("{given}" given) to generate a corresponding URL.';
            $allowed_values = implode(', ', array_keys($this->steps));
            throw new InvalidParameterException(strtr($message, [
                '{parameter}' => 'this->current_step',
                '{route}' => $request->attributes->get('_route'),
                '{expected}' => $allowed_values,
                '{given}' => $this->currentStep
            ]));
        }

        if (!$this->steps[$this->currentStep]['access']) {
            return $this->redirectToRoute($request->attributes->get('_route'));
        }

        $form = $this->getFormStep();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->has('prev') && $form->get('prev')->isClicked()) {
            return $this->redirectToRoute($request->attributes->get('_route'), ['step' => $this->prevStep]);
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $session_data = $this->session->get('install', []);
            $max_step = $this->session->get('install_max_step_index', 0);

            if ($max_step < $this->steps[$this->currentStep]['index']) {
                $this->session->set('install_max_step_index', $this->steps[$this->currentStep]['index']);
            }

            if ($this->currentStep === array_key_last($this->steps)) {
//                $phpBinaryFinder = new PhpExecutableFinder();
//                $phpBinaryPath = $phpBinaryFinder->find();
//                $projectRoot = $this->get('kernel')->getProjectDir();
//
//                $process = new Process([$phpBinaryPath, $projectRoot . '/bin/console', 'myapp:files:export', json_encode($filters)]);
//                $process->setTimeout(36000); //10min
//
//                // let the process run in the background
//                $this->get('event_dispatcher')->addListener(KernelEvents::TERMINATE, function() use($process) {
//                    $process->start();
//                    $process->wait();
//                });
                $this->redirect('/');
            }
            else {
                $session_data[$this->currentStep] = $form->getData();
                $this->session->set('install', $session_data);
                return $this->redirectToRoute($request->attributes->get('_route'), ['step' => $this->nextStep]);
            }
        }

        // render the form if it is the first request or if the validation failed
        return $this->render('install/index.html.twig', [
            'steps' => $this->steps,
            'currentStep' => $this->currentStep,
            'form' => $form->createView(),
        ]);
    }

    protected function getFormStep() {
        if (empty($this->currentStep)) {
            throw new RuntimeException();
        }

        $session_data = $this->session->get('install', []);
        $formOptions = [
            'attr' => [
                'autocomplete' => 'off',
            ],
        ];
        $validatorClassName = '\App\Validator\Install' . ucfirst($this->currentStep);
        if (class_exists($validatorClassName)) {
            $formOptions['constraints'] = [
                new $validatorClassName(),
            ];
        }
        $data = !empty($session_data[$this->currentStep]) ? $session_data[$this->currentStep] : ($this->step[$this->currentStep]['default_value'] ?? NULL);
        $form = $this->createFormBuilder($data, $formOptions);
        switch ($this->currentStep) {
            case 'db':
                $form
                    ->add('driver', ChoiceType::class, [
                        'constraints' => [
                            new NotBlank(),
                        ],
                        'choices'  => [
                            'DB2' => 'db2',
                            'SqlServer' => 'mssql',
                            'Mysql/MariaDB' => 'mysql',
                            'Amazon RDS' => 'mysql2',
                            'Postgres' => 'postgres',
                            'Sqlite' => 'sqlite',
                        ],
                        'empty_data' => 'mysql',
                        'label' => $this->translator->trans('Type', [], 'form'),
                    ])
                    ->add('host', TextType::class, [
                        'constraints' => [
                            new NotBlank(),
                        ],
                        'label' => $this->translator->trans('Host', [], 'form'),
                    ])
                    ->add('user', TextType::class, [
                        'constraints' => [
                            new NotBlank(),
                        ],
                        'label' => $this->translator->trans('User', [], 'form'),
                    ])
                    ->add('password', PasswordType::class, [
                        'constraints' => [
                            new NotBlank(),
                        ],
                        'label' => $this->translator->trans('Password', [], 'form'),
                    ])
                    ->add('database', TextType::class, [
                        'constraints' => [
                            new NotBlank(),
                        ],
                        'label' => $this->translator->trans('Database', [], 'form'),
                    ]);
                break;

            case 'email':
                $form
                    ->add('type', ChoiceType::class, [
                        'constraints' => [
                            new NotBlank(),
                        ],
                        'choices'  => [
                            $this->translator->trans('None') => 'null',
                            'Smtp' => 'smtp',
                            'Smtps' => 'smtps',
                            'Sendmail' => 'sendmail',
                            $this->translator->trans('Sendmail and smtp') => 'sendmail+smtp',
                            $this->translator->trans('Native') => 'native',
                        ],
                        'empty_data' => 'mysql',
                        'label' => $this->translator->trans('Type', [], 'form'),
                    ])
                    ->add('command', TextType::class, [
                        'required' => FALSE,
                        'constraints' => [
                            new Conditional(
                                'type',
                                ['sendmail', 'sendmail+smtp'],
                                [
                                    new NotBlank(),
                                ]
                            ),
                        ],
                        'label' => $this->translator->trans('Command executed by sendmail transport', [], 'form'),
                    ])
                    ->add('local_domain', TextType::class, [
                        'required' => FALSE,
                        'constraints' => [
                            new Conditional(
                                'type',
                                ['smtp', 'smtps'],
                                [
                                    new NotBlank(),
                                ]
                            ),
                        ],
                        'label' => $this->translator->trans('Domain name used in HELO command', [], 'form'),
                    ])
                    ->add('restart_threshold', IntegerType::class, [
                        'required' => FALSE,
                        'constraints' => [
                            new Conditional(
                                'type',
                                ['smtp', 'smtps'],
                                [
                                    new NotBlank(),
                                ]
                            ),
                        ],
                        'label' => $this->translator->trans('Maximum number of messages to send before re-starting the transport', [], 'form'),
                    ])
                    ->add('restart_threshold_sleep', IntegerType::class, [
                        'required' => FALSE,
                        'constraints' => [
                            new Conditional(
                                'type',
                                ['smtp', 'smtps'],
                                [
                                    new NotBlank(),
                                ]
                            ),
                        ],
                        'label' => $this->translator->trans('Number of seconds to sleep between stopping and re-starting the transport', [], 'form'),
                    ])
                    ->add('ping_threshold', IntegerType::class, [
                        'required' => FALSE,
                        'constraints' => [
                            new Conditional(
                                'type',
                                ['smtp', 'smtps'],
                                [
                                    new NotBlank(),
                                ]
                            ),
                        ],
                        'label' => $this->translator->trans('Minimum number of seconds between two messages required to ping the server', [], 'form'),
                    ])
                    ->add('max_per_second', IntegerType::class, [
                        'required' => FALSE,
                        'constraints' => [
                            new Conditional(
                                'type',
                                ['smtp', 'smtps'],
                                [
                                    new NotBlank(),
                                ]
                            ),
                        ],
                        'label' => $this->translator->trans('Number of messages to send per second (0 to disable this limitation)', [], 'form'),
                    ]);
                break;
            case 'check2':
                break;
        }

        $form->add('current_step', HiddenType::class, [
            'empty_data' => $this->currentStep,
        ]);
        if (!empty($this->prevStep)) {
            $form->add('prev', SubmitType::class, [
                'label' => $this->translator->trans('Previous', [], 'form'),
            ]);
        }
        if (!empty($this->nextStep)) {
            $form->add('next', SubmitType::class, [
                'label' => $this->translator->trans('Next', [], 'form'),
            ]);
        }
        else {
            $form->add('save', SubmitType::class, [
                'label' => $this->translator->trans('Save', [], 'form'),
            ]);
        }

        return $form->getForm();
    }
}
