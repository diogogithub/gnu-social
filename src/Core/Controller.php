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
 * Base class for controllers
 *
 * @package  GNUsocial
 * @category Controller
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use function App\Core\I18n\_m;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * @method int    int(string $param)
 * @method bool   bool(string $param)
 * @method string string(string $param)
 * @method string params(string $param)
 * @method mixed  handle(Request $request, mixed ...$extra)
 */
abstract class Controller extends AbstractController implements EventSubscriberInterface
{
    private array $vars         = [];
    protected ?Request $request = null;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * TODO: Not currently used, so not tested, but should be
     *
     * @codeCoverageIgnore
     */
    public function __invoke(Request $request)
    {
        $this->request = $request;
        $class         = static::class;
        $method        = 'on' . ucfirst(mb_strtolower($request->getMethod()));
        $attributes    = array_diff_key($request->attributes->get('_route_params'), array_flip(['_format', '_fragment', '_locale', 'template', 'accept', 'is_system_path']));
        if (method_exists($class, $method)) {
            return $this->{$method}($request, ...$attributes);
        } else {
            return $this->handle($request, ...$attributes);
        }
    }

    /**
     * Symfony event when it's searching for which controller to use
     */
    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();
        $request    = $event->getRequest();

        $this->request = $request;

        $this->vars = ['controller' => $controller, 'request' => $request];
        $user       = Common::user();
        if ($user !== null) {
            $this->vars['current_actor'] = $user->getActor();
        }

        $event->stopPropagation();
        return $event;
    }

    /**
     * Symfony event when the controller result is not a Response object
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function onKernelView(ViewEvent $event)
    {
        $request  = $event->getRequest();
        $response = $event->getControllerResult();
        if (!\is_array($response)) {
            // This means it's not one of our custom format responses, nothing to do
            // @codeCoverageIgnoreStart
            return $event;
            // @codeCoverageIgnoreEnd
        }

        $this->vars = array_merge_recursive($this->vars, $response);

        $template = $this->vars['_template'] ?? null;
        Event::handle('OverrideTemplate', [$this->vars, &$template]); // Allow plugins to replace the template used for anything
        unset($this->vars['_template'], $response['_template']);

        // Respond in the most preferred acceptable content type
        $route              = $request->get('_route');
        $accept             = $request->getAcceptableContentTypes() ?: ['text/html'];
        $format             = $request->getFormat($accept[0]);
        $potential_response = null;
        if (Event::handle('ControllerResponseInFormat', [
            'route' => $route,
            'accept_header' => $accept,
            'vars' => $this->vars,
            'response' => &$potential_response,
        ]) !== Event::stop) {
            switch ($format) {
            case 'json':
                $event->setResponse(new JsonResponse($response));
                break;
            case 'html':
                if ($template !== null) {
                    $event->setResponse($this->render($template, $this->vars));

                    /*                    // Setting the Content-Security-Policy response header
                                        $policy = "default-src 'self';"
                                            . "script-src 'strict-dynamic' https: http:;"
                                            . "object-src 'none'; base-uri 'none'";
                                        $potential_response = $event->getResponse();
                                        $potential_response->headers->set('Content-Security-Policy', $policy);
                                        $potential_response->headers->set('X-Content-Security-Policy', $policy);
                                        $potential_response->headers->set('X-WebKit-CSP', $policy);*/

                    break;
                } else {
                    // no break, goto default
                }
                // no break
            default:
                throw new ClientException(_m('Unsupported format: {format}', ['format' => $format]), 406); // 406 Not Acceptable
            }
        } else {
            if (\is_null($potential_response)) {
                // TODO BugFoundException
                Log::critical($m = "ControllerResponseInFormat for route '{$route}' returned Event::stop but didn't provide a response");
                throw new ServerException(_m($m, ['route' => $route]));
            }
            $event->setResponse($potential_response);
        }

        Event::handle('CleanupModule');

        return $event;
    }

    /**
     * Symfony event when the controller throws an exception
     *
     * @codeCoverageIgnore
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $except = $event->getThrowable();
        if ($_ENV['APP_ENV'] !== 'dev') {
            // TODO: This is where our custom exception pages could go
            // $event->setResponse((new Response())->setStatusCode(455));
        }
        do {
            if ($except instanceof RedirectException) {
                if (($redir = $except->redirect_response) != null) {
                    $event->setResponse($redir);
                } else {
                    $event->setResponse(new RedirectResponse($event->getRequest()->getPathInfo()));
                }
            }
        } while ($except != null && ($except = $except->getPrevious()) != null);

        Event::handle('CleanupModule');

        return $event;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::EXCEPTION  => 'onKernelException',
            KernelEvents::VIEW       => 'onKernelView',
        ];
    }

    /**
     * Get and convert GET parameters. Can be called with `int`, `bool`, `string`, etc
     *
     * @throws Exception
     * @throws ValidatorException
     *
     * @return null|array|bool|int|string the value or null if no parameter exists
     */
    public function __call(string $method, array $args): array|bool|int|string|null
    {
        switch ($method) {
        case 'int':
            return $this->request->query->getInt($args[0]);
        case 'bool':
            return $this->request->query->getBoolean($args[0]);
        case 'string':
            return $this->request->query->get($args[0]);
        case 'params':
            return $this->request->query->all();
        default:
            // @codeCoverageIgnoreStart
            Log::critical($m = "Method '{$method}' on class App\\Core\\Controller not found (__call)");
            throw new Exception($m);
            // @codeCoverageIgnoreEnd
        }
    }
}
