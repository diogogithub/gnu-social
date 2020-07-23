<?php

namespace App\Controller;

use App\Core\Controller;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class Security extends Controller
{
    public function login(AuthenticationUtils $authenticationUtils)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('main_all');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $last_username = $authenticationUtils->getLastUsername();

        return ['_template' => 'security/login.html.twig', 'last_username' => $last_username, 'error' => $error];
    }

    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
