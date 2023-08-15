<?php

namespace Install\Controller;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Tools\DsnParser;
use Install\Entity\Install;
use Install\Form\InstallType;
use Install\Service\InstallManager;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\EventListener\MessageListener;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class InstallController extends AbstractController
{
    #[Route('/install')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('install_index_locale', ['_locale' => $this->getParameter('app.default_locale')]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/install', name: 'install_index_locale')]
    public function index(
        Request $request,
        TranslatorInterface $translator,
        InstallManager $installManager,
    ): Response
    {
        if(!empty($_ENV['DATABASE_URL'])) {
            try {
                $dsnParser  = new DsnParser(Install::$driverSchemeAliases);
                $configuration = new Configuration();
                $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
                $conn = DriverManager::getConnection(
                    $dsnParser->parse($_ENV['DATABASE_URL']),
                    $configuration
                );
                if (!empty($conn->createSchemaManager()->listTables([Install::TABLE_CHECK_INSTALLER]))) {
                    return $this->redirect('/');
                }
            } finally {
                // Do nothing, if error occured, then database is invalid.
            }
        }

        /**
         * @var Install $install
         */
        $install = $request->getSession()->get('install_model', new Install());
        $steps = InstallType::getSteps();
        $current_step = $request->getSession()->get('install_step', $steps[0]);
        $current_step_index = array_search($current_step, $steps);
        $validationGroups = [$current_step];
        if ($request->request->has('install')){
            if (isset($request->request->all('install')['previous'])) {
                $request->getSession()->set('install_step', $steps[$current_step_index-1]);
                return $this->redirect($request->getUri());
            }
            elseif (isset($request->request->all('install')['check_email'])) {
                $validationGroups = ['email', 'check_email'];
            }
        }
        $form = $this->createForm(InstallType::class, $install, ['step' => $current_step, 'validation_groups' => $validationGroups]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (isset($request->request->all('install')['check_email'])) {
                $messageListener = new MessageListener(null, new BodyRenderer($this->container->get('twig')));
                $eventDispatcher = new EventDispatcher();
                $eventDispatcher->addSubscriber($messageListener);
                $transport = Transport::fromDsn($install->getEmailDsn(), $eventDispatcher);
                $mailer = new Mailer($transport, null, $eventDispatcher);
                $email = (new TemplatedEmail())
                    ->from(new Address('no-reply@drupguard.com', 'Drupguard'))
                    ->to($install->getEmail())
                    ->subject($translator->trans('Welcome to Drupguard !', [], 'emails'))
                    ->htmlTemplate('emails/check.html.twig')
                    ->context([]);
                $mailer->send($email);
                return $this->redirect($request->getUri());
            }
            elseif (end($steps) === $current_step) {
                try {
                    $installManager->processInstall($install);
                    $request->getSession()->remove('install_model');
                    $request->getSession()->remove('install_step');
                    return $this->redirect('/');
                }
                catch (\Exception $exception) {
                    $form->addError(new FormError(
                        $translator->trans('An error occured during installation process. Detail: %detail%.', ['%detail%' => $exception->getMessage()], 'install'),
                        'An error occured during installation process.'
                    ));
                }
            }
            else {
                $request->getSession()->set('install_model', $install);
                $request->getSession()->set('install_step', $steps[$current_step_index+1]);
                return $this->redirect($request->getUri());
            }
        }
        $stepsLabel = [];
        foreach ($steps as $step) {
            $stepsLabel[$step] = $translator->trans(ucfirst($step), [], 'install');
        }
        return $this->render('install/index.html.twig', [
            'form' => $form,
            'install' => $install,
            'steps' => $stepsLabel,
            'current_step' => $current_step,
            'current_step_index' => $current_step_index
        ]);
    }
}
