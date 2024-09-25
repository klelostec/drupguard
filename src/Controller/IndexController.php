<?php

namespace App\Controller;

use App\Controller\Crud\UserCrudController;
use App\Entity\Group;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use App\Security\Roles;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

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
                ->setPermission(Roles::ADMIN),
        ];
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        // Usually it's better to call the parent method because that gives you a
        // user menu with some menu items already created ("sign out", "exit impersonation", etc.)
        // if you prefer to create the user menu from scratch, use: return UserMenu::new()->...
        return parent::configureUserMenu($user)
            ->displayUserAvatar(false)
            // you can use any type of menu item, except submenus
            ->addMenuItems([
                MenuItem::linkToUrl('My Profile', 'fa fa-id-card',
                    $this->container->get(AdminUrlGenerator::class)
                        ->setController(UserCrudController::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($user->getId())
                        ->generateUrl()
                ),
                MenuItem::section(),
                MenuItem::linkToLogout('Logout', 'fa fa-sign-out'),
            ]);
    }
}
