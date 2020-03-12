<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App;

class NetworkPublic extends AbstractController
{
    public function __invoke()
    {
        return $this->render('network/public.html.twig', []);
    }
}
