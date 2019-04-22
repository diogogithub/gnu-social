<?php

if (!defined('GNUSOCIAL')) {
    exit(1);
}

class NodeinfoJRDAction extends XrdAction
{
    const NODEINFO_2_0_REL = 'http://nodeinfo.diaspora.software/ns/schema/2.0';

    protected $defaultformat = 'json';

    protected function setXRD()
    {
        $this->xrd->links[] = new XML_XRD_Element_link(self::NODEINFO_2_0_REL, common_local_url('nodeinfo_2_0'));
    }
}
