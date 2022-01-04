<?php

declare(strict_types = 1);

namespace App\Core\Form;

use App\Core\Cache;
use function App\Core\I18n\_m;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Util\ServerParams;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class FormTypeNonceExtension extends AbstractTypeExtension implements EventSubscriberInterface
{
    public function __construct(
        public CsrfTokenManagerInterface $token_manager,
        public ServerParams $serverParams = new ServerParams(),
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents()
    {
        return [FormEvents::PRE_SUBMIT => 'preSubmit'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['nonce_protection' => true]);
    }

    /**
     * Before the form gets to the controller, piggy-back on the CSRF token and use it as a nonce, ensuring it was only submitted once
     */
    public function preSubmit(FormEvent $event)
    {
        $form                    = $event->getForm();
        $postRequestSizeExceeded = 'POST' === $form->getConfig()->getMethod() && $this->serverParams->hasPostMaxSizeBeenExceeded();

        if ($form->isRoot() && $form->getConfig()->getOption('compound') && !$postRequestSizeExceeded) {
            $data        = $event->getData();
            $token_id    = $form->getConfig()->getOption('csrf_token_id') ?: ($form->getName() ?: \get_class($form->getConfig()->getType()->getInnerType()));
            $token_value = \is_string($data['_token'] ?? null) ? $data['_token'] : null;
            $csrf_token  = new CsrfToken($token_id, $token_value);

            if (null === $token_value || !$this->token_manager->isTokenValid($csrf_token) || Cache::incr("nonce:{$token_value}") !== 1) { // TODO add TTL
                $form->addError(new FormError(_m('Invalid nonce'), null, [], null, $csrf_token));
            }
        }
    }
}
