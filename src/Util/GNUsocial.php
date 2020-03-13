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

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

use App\Util\Log;
use App\Util\GSEvent;
use App\Util\I18n;

class GNUsocial implements EventSubscriberInterface
{
    protected ContainerInterface $container;
    protected LoggerInterface $logger;
    protected TranslatorInterface $translator;

    public function __construct(ContainerInterface $container,
                                LoggerInterface $logger,
                                TranslatorInterface $translator)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->translator = $translator;
    }

    public function onKernelRequest(RequestEvent $event,
                                    string $event_name,
                                    $event_dispatcher): RequestEvent
    {
        if (!defined('INSTALLDIR')) {
            define('INSTALLDIR', dirname(__DIR__));
            define('SRCDIR', INSTALLDIR . '/src');
            define('PUBLICDIR', INSTALLDIR . '/public');
            define('GNUSOCIAL_ENGINE', 'GNU social');
            // MERGE Change to https://gnu.io/social/
            define('GNUSOCIAL_ENGINE_URL', 'https://gnusocial.network/');
            // MERGE Change to https://git.gnu.io/gnu/gnu-social
            define('GNUSOCIAL_ENGINE_REPO_URL', 'https://notabug.org/diogo/gnu-social/');
            // Current base version, major.minor.patch
            define('GNUSOCIAL_BASE_VERSION', '3.0.0');
            // 'dev', 'alpha[0-9]+', 'beta[0-9]+', 'rc[0-9]+', 'release'
            define('GNUSOCIAL_LIFECYCLE', 'dev');
            define('GNUSOCIAL_VERSION', GNUSOCIAL_BASE_VERSION . '-' . GNUSOCIAL_LIFECYCLE);
            define('GNUSOCIAL_CODENAME', 'Big bang');

            /* Work internally in UTC */
            date_default_timezone_set('UTC');

            /* Work internally with UTF-8 */
            mb_internal_encoding('UTF-8');

            Log::setLogger($this->logger);
            GSEvent::setDispatcher($event_dispatcher);
            I18n::setTranslator($this->translator);
        }


        GSEvent::addHandler('test', function ($x) {
            Log::info(_m("Logging from an event " . var_export($x, true)));
        });

        return $event;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onKernelRequest'
        );
    }
}
