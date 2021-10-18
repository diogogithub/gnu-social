<?php

namespace Component\FreeNetwork\Util;

use App\Core\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use XML_XRD;

abstract class XrdController extends Controller
{
    protected string $default_mimetype = Discovery::JRD_MIMETYPE;

    protected XML_XRD $xrd;

    /*
     * Configures $this->xrd which will later be printed. Must be
     * implemented by child classes.
     */
    abstract protected function setXRD();

    public function __construct(RequestStack $requestStack)
    {
        parent::__construct($requestStack);

        if ($this->request->headers->get('format', null) === null) {
            $this->request->headers->set('format', $this->default_mimetype);
        }

        $this->xrd = new XML_XRD();
    }

    public function handle(Request $request): array
    {
        $this->setXRD();
        return ['xrd' => $this->xrd, 'default_mimetype' => $this->default_mimetype];
    }
}