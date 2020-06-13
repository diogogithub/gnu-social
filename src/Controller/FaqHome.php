<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

/**
 * FAQ main page
 *
 * @package  GNUsocial
 * @category Controller
 *
 * @author    Eliseu Amaro <eliseu@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class FaqHome extends AbstractController
{
    /**
     * @Route("/doc/faq", name="doc_faq")
     */
    public function home()
    {
        return $this->render('faq/home.html.twig');
    }

    /**
     * @Route("/doc/help", name="doc_help")
     */
    public function help()
    {
        return $this->render('faq/help.html.twig');
    }

    /**
     * @Route("/doc/about", name="doc_about")
     */
    public function about()
    {
        return $this->render('faq/about.html.twig');
    }

    /**
     * @Route("/doc/contact", name="doc_contact")
     */
    public function contact()
    {
        return $this->render('faq/contact.html.twig');
    }

    /**
     * @Route("/doc/tags", name="doc_tags")
     */
    public function tags()
    {
        return $this->render('faq/tags.html.twig');
    }

    /**
     * @Route("/doc/groups", name="doc_groups")
     */
    public function groups()
    {
        return $this->render('faq/groups.html.twig');
    }

    /**
     * @Route("/doc/api", name="doc_api")
     */
    public function api()
    {
        return $this->render('faq/api.html.twig');
    }

    /**
     * @Route("/doc/openid", name="doc_openid")
     */
    public function openid()
    {
        return $this->render('faq/openid.html.twig');
    }
}