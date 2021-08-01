<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AutocompleteController extends AbstractController
{
    /**
     * @Route("/autocomplete/user", name="autocomplete_user")
     */
    public function user(Request $request, UserRepository $repository): Response
    {
        $users = $repository->findByFirstOrLastName(
            $request->query->get('term'),
            $request->query->get('exclude')
        );
        $ret = [];
        foreach ($users as $u) {
            $ret[] = [
                'id' => $u->getId(),
                'text' => (string) $u
            ];
        }
        return $this->json($ret);
    }
}
