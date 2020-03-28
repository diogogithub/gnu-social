<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TestController extends AbstractController
{
    public function __invoke()
    {
        return new Response('<div style="background: #333; text: #999"> Test controller </div>');
    }
}
