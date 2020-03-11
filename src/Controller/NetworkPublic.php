<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Util\GSEvent;

class NetworkPublic extends AbstractController
{
    public function __invoke()
    {
        GSEvent::handle('test', ['foobar']);
        return $this->render('network/public.html.twig', []);
    }
}
