<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use App\Security\Roles;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractDashboardController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('index/index.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Drupguard');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToDashboard('Drupguard', 'fa fa-home'),
            MenuItem::linkToCrud('Projects', 'fa fa-briefcase', Project::class),
            MenuItem::subMenu('Administration', 'fa fa-gears')
                ->setSubItems([
                    MenuItem::linkToCrud('Users', 'fa fa-user', User::class),
                    MenuItem::linkToCrud('Groups', 'fa fa-users', Group::class),
                    MenuItem::linkToCrud('Project members', 'fa fa-address-card', ProjectMember::class),
                ])
                ->setPermission(Roles::ADMIN)
        ];
    }
}
