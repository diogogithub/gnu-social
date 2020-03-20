<?php

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

/**
 * Main GNU social entry point
 *
 * @package   GNUsocial
 * @category  Framework
 *
 * @author    Brenda Wallace <shiny@cpan.org>
 * @author    Brion Vibber <brion@pobox.com>
 * @author    Brion Vibber <brion@status.net>
 * @author    Christopher Vollick <candrews@integralblue.com>
 * @author    CiaranG <ciaran@ciarang.com>
 * @author    Craig Andrews <candrews@integralblue.com>
 * @author    Craig Andrews <evan@status.net>
 * @author    Evan Prodromou <evan@controlezvous.ca>
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @author    Evan Prodromou <evan@prodromou.name>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Gina Haeussge <osd@foosel.net>
 * @author    James Walker <walkah@walkah.net>
 * @author    Jeffery To <candrews@integralblue.com>
 * @author    Jeffery To <jeffery.to@gmail.com>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Mike Cochrane <mikec@mikenz.geek.nz>
 * @author    Robin Millette <millette@controlyourself.ca>
 * @author    Sarven Capadisli <csarven@controlyourself.ca>
 * @author    Sarven Capadisli <csarven@status.net>
 * @author    Siebrand Mazeland <s.mazeland@xs4all.nl>
 * @author    Tom Adams <candrews@integralblue.com>
 * @author    Tom Adams <tom@holizz.com>
 * @author    Zach Copley <zach@status.net>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2010, 2018-2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class GNUsocial implements EventSubscriberInterface
{
    protected ContainerInterface $container;
    protected LoggerInterface $logger;
    protected TranslatorInterface $translator;

    public function __construct(ContainerInterface $container,
                                LoggerInterface $logger,
                                TranslatorInterface $translator)
    {
        $this->container  = $container;
        $this->logger     = $logger;
        $this->translator = $translator;
    }

    public function onKernelRequest(RequestEvent $event,
                                    string $event_name,
                                    $event_dispatcher): RequestEvent
    {
        Log::setLogger($this->logger);
        GSEvent::setDispatcher($event_dispatcher);
        I18n::setTranslator($this->translator);
        ExtensionManager::loadExtensions();

        return $event;
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }
}
