<?php

namespace Plugin\Test\Controller;

use App\Core\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Controller
{
    public function __invoke(Request $request)
    {
        return new Response('<html style="background: #333; color: #ccc"> Test controller </div>');
    }
}
