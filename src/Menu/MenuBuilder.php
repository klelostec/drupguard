<?php

namespace App\Menu;

use App\Entity\User;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Security\Core\Security;

class MenuBuilder
{
    private FactoryInterface $factory;
    private Security $security;

    /**
     * Add any other dependency you need...
     */
    public function __construct(FactoryInterface $factory, Security $security)
    {
        $this->factory = $factory;
        $this->security = $security;
    }

    public function createMainMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root', ['childrenAttributes' => ['class' => 'navbar-nav mr-auto']]);

        $menu->addChild('Home', ['route' => 'app_home']);

        if ($this->security->isGranted('ROLE_USER')) {
            /**
             * @var User $user
             */
            $user = $this->security->getUser();
            // administration
            if ($this->security->isGranted('ROLE_ADMIN')) {
                $adminMenu = $menu->addChild('Administration', ['attributes' => ['dropdown' => true]]);
                $adminMenu->addChild('Modules Statistics', ['route' => 'statistics_index']);
                $adminMenu->addChild('Users', ['route' => 'user_index']);
            }
            $userMenu = $menu->addChild($user->getUsername(), ['attributes' => ['dropdown' => true, 'icon' => 'fas fa-user fa-lg']]);
            $userMenu->addChild('Profile', ['route' => 'app_profile']);

            if (!empty($user->getTokenApi())) {
                $userMenu->addChild('API Documentation', [
                    'route' => 'app.swagger_ui',
                    'linkAttributes' => ['target' => '_blank'],
                ]);
            }

            $userMenu->addChild('Logout', ['route' => 'app_logout']);
        } else {
            $menu->addChild('Login', ['route' => 'app_login']);
        }
        return $menu;
    }
}
