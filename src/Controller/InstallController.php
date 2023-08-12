<?php

namespace App\Controller;

use App\Entity\Install;
use App\Form\InstallFlow;
use Craue\FormFlowBundle\Util\FormFlowUtil;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class InstallController extends AbstractController
{
    /**
     * @var FormFlowUtil
     */
    private $formFlowUtil;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /** @var EntityManagerProvider|null */
    private $entityManagerProvider;


    public function __construct(FormFlowUtil $formFlowUtil, Environment $twig, EntityManagerInterface $entityManager, ?EntityManagerProvider $entityManagerProvider) {
		$this->formFlowUtil = $formFlowUtil;
		$this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->entityManagerProvider = $entityManagerProvider;
    }

    public function index(Request $request, InstallFlow $flow): Response
    {
        $formData = new Install();
        $flow->bind($formData);

        $form = $submittedForm = $flow->createForm();
        if ($flow->isValid($submittedForm)) {
            $flow->saveCurrentStepData($submittedForm);

            if ($flow->nextStep()) {
                // create form for next step
                $form = $flow->createForm();
            } else {
                /**
                 * @var Install $install
                 */
                $install = $form->getData();
                $hash = $this->createBatchProcess($install);
                $flow->reset();

                return $this->redirectToRoute('_FormFlow_start');
            }
        }

        if ($flow->redirectAfterSubmit($submittedForm)) {
            $params = $this->formFlowUtil->addRouteParameters(array_merge($request->query->all(),
                $request->attributes->get('_route_params')), $flow);

            return $this->redirectToRoute($request->attributes->get('_route'), $params);

        }

        return new Response($this->twig->render('install/index.html.twig', [
            'form' => $form->createView(),
            'flow' => $flow,
            'formData' => $formData,
        ]));
    }

    /**
     * @return string
     */
    protected function createBatchProcess(Install $install) {
        try {
            $db_url = $install->db_driver . '://' .
                urlencode($install->db_user) . ':' .
                urlencode($install->db_password) . '@' .
                urlencode($install->db_host);
            $tmpConnection = DriverManager::getConnection(['url' => $db_url]);
            $schemaManager = method_exists($tmpConnection, 'createSchemaManager')
                ? $tmpConnection->createSchemaManager()
                : $tmpConnection->getSchemaManager();

            $databases = $tmpConnection->createSchemaManager()->listDatabases();
            if (!in_array($install->db_database, $databases)) {
                $name = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($install->db_database);
                $schemaManager->createDatabase($name);
            }

            $tmpConnection->close();

            $em = $this->entityManagerProvider->getManager('default');
            $test = TRUE;


//            $batch = new BatchProcess();
//            $batch->setCreated(new \DateTime());
//            $batch->setExpiredAt((new \DateTime())->add(new \DateInterval('1h')));
//            $batch->setRedirectUrl($this->generateUrl('app_index'));
//            $this->entityManager->persist($batch);
//            $this->entityManager->flush();

//            return $batch->getId();
        }
        catch (\Exception $e) {

        }
    }
}
