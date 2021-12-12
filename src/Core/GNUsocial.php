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
 * Main GNU social entry point
 *
 * @package   GNUsocial
 * @category  Framework
 *
 * StatusNet and GNU social 1
 *
 * @author    Refer to CREDITS.md
 * @copyright 2010 Free Software Foundation, Inc http://www.fsf.org
 *
 * GNU social 2
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 *
 * GNU social 3
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2018-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use App\Core\DB\DB;
use App\Core\I18n\I18n;
use App\Core\Queue\Queue;
use App\Core\Router\Router;
use App\Kernel;
use App\Security\EmailVerifier;
use App\Util\Common;
use App\Util\Exception\ConfigurationException;
use App\Util\Formatting;
use Doctrine\ORM\EntityManagerInterface;
use HtmlSanitizer\SanitizerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security as SSecurity;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Twig\Environment;

/**
 * @codeCoverageIgnore
 */
class GNUsocial implements EventSubscriberInterface
{
    use TargetPathTrait;

    protected bool $initialized = false;
    protected LoggerInterface $logger;
    protected TranslatorInterface $translator;
    protected EntityManagerInterface $entity_manager;
    protected RouterInterface $router;
    protected FormFactoryInterface $form_factory;
    protected MessageBusInterface $message_bus;
    protected EventDispatcherInterface $event_dispatcher;
    protected SessionInterface $session;
    protected SSecurity $security;
    protected ModuleManager $module_manager;
    protected HttpClientInterface $client;
    protected SanitizerInterface $sanitizer;
    protected ContainerBagInterface $config;
    protected Environment $twig;
    protected ?Request $request;
    protected MailerInterface $mailer_helper;
    protected VerifyEmailHelperInterface $email_verify_helper;
    protected ResetPasswordHelperInterface $reset_password_helper;

    /**
     * Symfony dependency injection gives us access to these services
     */
    public function __construct(
        LoggerInterface $logger,
        TranslatorInterface $trans,
        EntityManagerInterface $em,
        RouterInterface $router,
        FormFactoryInterface $ff,
        MessageBusInterface $mb,
        EventDispatcherInterface $ed,
        SessionInterface $sess,
        SSecurity $sec,
        ModuleManager $mm,
        HttpClientInterface $cl,
        SanitizerInterface $san,
        ContainerBagInterface $conf,
        Environment $twig,
        RequestStack $request_stack,
        MailerInterface $mailer,
        VerifyEmailHelperInterface $email_verify_helper,
        ResetPasswordHelperInterface $reset_helper,
    ) {
        $this->logger                = $logger;
        $this->translator            = $trans;
        $this->entity_manager        = $em;
        $this->router                = $router;
        $this->form_factory          = $ff;
        $this->message_bus           = $mb;
        $this->event_dispatcher      = $ed;
        $this->session               = $sess;
        $this->security              = $sec;
        $this->module_manager        = $mm;
        $this->client                = $cl;
        $this->sanitizer             = $san;
        $this->config                = $conf;
        $this->twig                  = $twig;
        $this->request               = $request_stack->getCurrentRequest();
        $this->mailer_helper         = $mailer;
        $this->email_verify_helper   = $email_verify_helper;
        $this->reset_password_helper = $reset_helper;

        $this->initialize();
    }

    /**
     * Store these services to be accessed statically and load modules
     *
     * @throws ConfigurationException
     */
    public function initialize(): void
    {
        if (!$this->initialized) {
            Common::setupConfig($this->config);
            if (!\is_null($this->request)) {
                Common::setRequest($this->request);
            }
            Log::setLogger($this->logger);
            Event::setDispatcher($this->event_dispatcher);
            I18n::setTranslator($this->translator);
            DB::setManager($this->entity_manager);
            Form::setFactory($this->form_factory);
            Queue::setMessageBus($this->message_bus);
            Security::setHelper($this->security, $this->sanitizer);
            Router::setRouter($this->router);
            HTTPClient::setClient($this->client);
            Formatting::setTwig($this->twig);
            EmailVerifier::setHelpers($this->email_verify_helper, $this->mailer_helper);
            Cache::setupCache();

            DB::initTableMap();

            // Events are preloaded on compilation, but set at runtime, along with configuration
            $this->module_manager->loadModules();

            $this->initialized = true;
        }
    }

    /**
     * Event very early on in the Symfony HTTP lifecycle, but after everything is registered
     * where we get access to the event dispatcher
     */
    public function onKernelRequest(RequestEvent $event): RequestEvent
    {
        $this->request = $event->getRequest();

        // Save the target path, so we can redirect back after logging in
        if (!(!$event->isMainRequest() || $this->request->isXmlHttpRequest() || Common::isRoute(['login', 'register', 'logout']))) {
            $this->saveTargetPath($this->session, 'main', $this->request->getBaseUrl());
        }

        $this->initialize();
        Event::handle('InitializeModule');
        return $event;
    }

    /**
     * Event after everything is initialized when using the `bin/console` command
     *
     * @throws ConfigurationException
     */
    public function onCommand(ConsoleCommandEvent $event): ConsoleCommandEvent
    {
        $this->initialize();
        return $event;
    }

    /**
     * Load configuration files
     *
     * Happens at "compile time"
     *
     * @codeCoverageIgnore
     */
    public static function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        // Overriding doesn't work as we want, overrides the top-most key, do it manually
        $local_file = INSTALLDIR . '/social.local.yaml';
        if (!file_exists($local_file)) {
            file_put_contents($local_file, "parameters:\n  locals:\n    gnusocial:\n");
        }

        // Load .local
        $loader->load($local_file);
        $locals = $container->getParameter('locals');
        $container->getParameterBag()->remove('locals');

        // Load normal config
        $loader->load(INSTALLDIR . '/social' . Kernel::CONFIG_EXTS, 'glob');
        $defaults = $container->getParameter('gnusocial');

        // Load module config
        $module_configs = ModuleManager::configureContainer($container, $loader);

        // Merge parameter $from with values already set in $to
        $merge_local_config = function ($from, $to = null) use ($container, $locals) {
            $to ??= $from;
            $wrapper = $container->hasParameter($to) ? $container->getParameter($to) : [];
            $content = [$from => $container->getParameter($from)];
            $container->getParameterBag()->remove($from);
            $locals  = $locals[$from] ?? [];
            $configs = array_replace_recursive($wrapper, $content, $locals);
            $container->setParameter($to, $configs);
        };

        // Override and merge any of the previous settings from the locals
        if (\is_array($locals)) {
            $merge_local_config('gnusocial');
            foreach ($module_configs as $mod => $type) {
                $loader->load(INSTALLDIR . \PATH_SEPARATOR . $type . \PATH_SEPARATOR . ucfirst($mod) . 'config' . Kernel::CONFIG_EXTS, 'glob');
                $defaults[$mod] = $container->getParameter($mod);
                $merge_local_config($mod, $type); // TODO likely broken
            }
        }
        $container->setParameter('gnusocial_defaults', $defaults);
    }

    /**
     * Tell Symfony which events we want to listen to, which Symfony detects and auto-wires
     * due to this implementing the `EventSubscriberInterface`
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            'console.command'     => 'onCommand',
        ];
    }
}
