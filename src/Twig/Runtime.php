<?php

declare(strict_types = 1);

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
 * Common utility functions
 *
 * @package   GNUsocial
 * @category  Util
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Twig;

use App\Core\Event;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Formatting;
use Functional as F;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class Runtime implements RuntimeExtensionInterface, EventSubscriberInterface
{
    private Request $request;
    public function setRequest(Request $req)
    {
        $this->request = $req;
    }

    public function isCurrentRouteActive(string ...$routes): string
    {
        return $this->isCurrentRoute('active', ...$routes);
    }

    public function isCurrentRoute(string $class, string ...$routes): string
    {
        $current_route = $this->request->attributes->get('_route');
        return F\some($routes, F\partial_left([Formatting::class, 'startsWith'], $current_route)) ? $class : '';
    }

    public function getNoteActions(Note $note)
    {
        $actions = [];
        Event::handle('AddNoteActions', [$this->request, $note, &$actions]);
        return $actions;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getConfig(...$args)
    {
        return Common::config(...$args);
    }

    /**
     * get stylesheets
     *
     * @return array|mixed
     * @codeCoverageIgnore
     */
    public function getShowStylesheets($route)
    {
        $styles = [];
        Event::handle('EndShowStyles', [&$styles, $route]);
        return $styles;
    }

    /**
     * @codeCoverageIgnore
     */
    public function handleEvent(string $event, ...$args)
    {
        $res    = [];
        $args[] = &$res;
        Event::handle($event, $args);
        return $res;
    }

    /**
     * Renders the Svg Icon template and returns it.
     *
     * @author Ângelo D. Moura <up201303828@fe.up.pt>
     */
    public function embedSvgIcon(Environment $twig, string $icon_name = '', string $icon_css_class = ''): string
    {
        return $twig->render('@public_path/assets/icons/' . $icon_name . '.svg.twig', ['iconClass' => $icon_css_class]);
    }

    public function isFirefox(): bool
    {
        $re_has_chrome = '/.*(?i)\bchrome\b.*/m';
        $re_has_gecko  = '/.*(?i)\bgecko\b.*/m';
        return (preg_match(pattern: $re_has_chrome, subject: $this->request->headers->get('User-Agent')) !== 1)
            && (preg_match(pattern: $re_has_gecko, subject: $this->request->headers->get('User-Agent')) === 1);
    }

    public function isInstanceOf($var, string $instance): bool
    {
        return $var instanceof $instance;
    }

    public function handleOverrideTemplateImport(string $template, string $default_import): string
    {
        $result = '';
        if (Event::handle('OverrideTemplateImport', [$template, $default_import, &$result]) !== Event::stop) {
            $result = $default_import;
        }
        return $result;
    }

    public function openDetails(?string $query, array $ids)
    {
        return in_array($query, $ids) ? 'open=""' : '';
    }

    // ----------------------------------------------------------

    /**
     * @codeCoverageIgnore
     */
    public function onKernelRequest(RequestEvent $event)
    {
        // Request is not a service, can't find a better way to get it
        $this->request = $event->getRequest();
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }
}
