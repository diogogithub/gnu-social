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

namespace Component\Subscription\Controller;

use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\RedirectException;
use Component\Collection\Util\ActorControllerTrait;
use Component\Collection\Util\Controller\CircleController;
use Component\Subscription\Subscription as SubscriptionComponent;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Collection of an actor's subscribers
 */
class Subscribers extends CircleController
{
    use ActorControllerTrait;
    public function subscribersByActorId(Request $request, int $id)
    {
        return $this->handleActorById(
            $id,
            fn ($actor) => [
                'actor' => $actor,
            ],
        );
    }

    public function subscribersByActorNickname(Request $request, string $nickname)
    {
        return $this->handleActorByNickname(
            $nickname,
            fn ($actor) => [
                '_template'        => 'collection/actors.html.twig',
                'title'            => _m('Subscribers'),
                'empty_message'    => _m('No subscribers.'),
                'sort_form_fields' => [],
                'page'             => $this->int('page') ?? 1,
                'actors'           => $actor->getSubscribers(),
            ],
        );
    }

    /**
     * @throws \App\Util\Exception\DuplicateFoundException
     * @throws \App\Util\Exception\NoLoggedInUser
     * @throws \App\Util\Exception\NotFoundException
     * @throws \App\Util\Exception\ServerException
     * @throws ClientException
     * @throws RedirectException
     */
    public function subscribersAdd(Request $request, int $object_id): array
    {
        $subject = Common::ensureLoggedIn();
        $object  = Actor::getById($object_id);
        $form    = Form::create(
            [
                ['subscriber_add', SubmitType::class, ['label' => _m('Subscribe!')]],
            ],
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!\is_null(SubscriptionComponent::subscribe($subject, $object))) {
                DB::flush();
                SubscriptionComponent::refreshSubscriptionCount($subject, $object);
            }

            // Redirect user to where they came from
            // Prevent open redirect
            if (!\is_null($from = $this->string('from'))) {
                if (Router::isAbsolute($from)) {
                    Log::warning("Actor {$object_id} attempted to reply to a note and then get redirected to another host, or the URL was invalid ({$from})");
                    throw new ClientException(_m('Can not redirect to outside the website from here'), 400); // 400 Bad request (deceptive)
                }

                // TODO anchor on element id
                throw new RedirectException(url: $from);
            }

            // If we don't have a URL to return to, go to the instance root
            throw new RedirectException('root');
        }

        return [
            '_template' => 'subscription/add_subscriber.html.twig',
            'form'      => $form->createView(),
            'object'    => $object,
        ];
    }

    /**
     * @throws \App\Util\Exception\DuplicateFoundException
     * @throws \App\Util\Exception\NoLoggedInUser
     * @throws \App\Util\Exception\NotFoundException
     * @throws \App\Util\Exception\ServerException
     * @throws ClientException
     * @throws RedirectException
     */
    public function subscribersRemove(Request $request, int $object_id): array
    {
        $subject = Common::ensureLoggedIn();
        $object  = Actor::getById($object_id);
        $form    = Form::create(
            [
                ['subscriber_remove', SubmitType::class, ['label' => _m('Unsubscribe')]],
            ],
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!\is_null(SubscriptionComponent::unsubscribe($subject, $object))) {
                DB::flush();
                SubscriptionComponent::refreshSubscriptionCount($subject, $object);
            }

            // Redirect user to where they came from
            // Prevent open redirect
            if (!\is_null($from = $this->string('from'))) {
                if (Router::isAbsolute($from)) {
                    Log::warning("Actor {$object_id} attempted to subscribe an actor and then get redirected to another host, or the URL was invalid ({$from})");
                    throw new ClientException(_m('Can not redirect to outside the website from here'), 400); // 400 Bad request (deceptive)
                }

                // TODO anchor on element id
                throw new RedirectException(url: $from);
            }

            // If we don't have a URL to return to, go to the instance root
            throw new RedirectException('root');
        }

        return [
            '_template' => 'subscription/remove_subscriber.html.twig',
            'form'      => $form->createView(),
            'object'    => $object,
        ];
    }
}
