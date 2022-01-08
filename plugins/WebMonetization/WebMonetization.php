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
 * WebMonetization for GNU social
 *
 * @package   GNUsocial
 * @category  Plugin
 *
 * @author    Phablulo <phablulo@gmail.com>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\WebMonetization;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Modules\Plugin;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use App\Util\Formatting;
use Plugin\WebMonetization\Entity\Wallet;
use Plugin\WebMonetization\Entity\WebMonetization as Monetization;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class WebMonetization extends Plugin
{
    public function onAppendRightPanelBlock($vars, Request $request, &$res): bool
    {
        $user = Common::actor();
        if (\is_null($user)) {
            return Event::next;
        }
        if (\is_null($vars)) {
            return Event::next;
        }

        $is_self     = null;
        $receiver_id = null;

        if ($vars['path'] === 'settings') {
            $is_self = true;
        } elseif ($vars['path'] === 'actor_view_nickname') {
            $is_self = $request->attributes->get('nickname') === $user->getNickname();
            if (!$is_self) {
                $receiver_id = DB::findOneBy(LocalUser::class, [
                    'nickname' => $request->attributes->get('nickname'),
                ], return_null: true)?->getId();
            }
        } elseif ($vars['path'] === 'actor_view_id') {
            $is_self = $request->attributes->get('id') == $user->getId();
            if (!$is_self) {
                $receiver_id = $request->attributes->get('id');
            }
        } else {
            return Event::next;
        }
        // if visiting self page, the user will see a form to add, remove or update his wallet
        if ($is_self) {
            $wallet = DB::findOneBy(Wallet::class, ['actor_id' => $user->getId()], return_null: true);
            $form   = Form::create([
                ['address', TextType::class, [
                    'label' => _m('Wallet address'),
                    'attr'  => [
                        'placeholder'  => _m('Wallet address'),
                        'autocomplete' => 'off',
                        'value'        => $wallet?->getAddress(),
                    ],
                ]],
                ['webmonetizationsave', SubmitType::class, [
                    'label' => _m('Save'),
                    'attr'  => [
                        'title' => _m('Save'),
                    ],
                ]],
            ]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                if (\is_null($wallet)) {
                    DB::persist(
                        Wallet::create([
                            'actor_id' => $user->getId(),
                            'address'  => $form->getData()['address'],
                        ]),
                    );
                } else {
                    $wallet->setAddress($form->getData()['address']);
                }
                DB::flush();
                throw new RedirectException();
            }

            $res[] = Formatting::twigRenderFile(
                'WebMonetization/widget.html.twig',
                ['user' => $user, 'the_form' => $form->createView()],
            );
        }
        // if visiting another user page, the user will see a form to start/stop donating to them
        else {
            $entry = DB::findOneBy(Monetization::class, ['sender' => $user->getId(), 'receiver' => $receiver_id], return_null: true);
            $label = $entry?->getActive() ? _m('Stop donating') : _m('Start donating');
            $form  = Form::create([
                ['toggle', SubmitType::class, [
                    'label' => $label,
                    'attr'  => [
                        'title' => $label,
                    ],
                ]],
            ]);
            $res[] = Formatting::twigRenderFile(
                'WebMonetization/widget.html.twig',
                ['user' => $user, 'the_form' => $form->createView()],
            );
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                if (\is_null($entry)) {
                    $entry = Monetization::create(
                        ['sender' => $user->getId(), 'receiver' => $receiver_id, 'active' => true, 'sent' => 0],
                    );
                    DB::persist($entry);
                } else {
                    $entry->setActive(!$entry->getActive());
                }
                DB::flush();
                // notify receiver!
                $rwallet = DB::findOneBy(Wallet::class, ['actor_id' => $receiver_id], return_null: true);
                $message = null;
                if ($entry->getActive()) {
                    if ($rwallet?->getAddress()) {
                        $message = '{nickname} is now donating to you!';
                    } else {
                        $message = '{nickname} wants to donate to you. Configure a wallet address to receive donations!';
                    }
                    $activity = Activity::create([
                        'actor_id'    => $user->getId(),
                        'verb'        => 'offer',
                        'object_type' => 'webmonetization',
                        'object_id'   => $entry->getId(),
                        'source'      => 'web',
                    ]);
                } else {
                    $message = '{nickname} is no longer donating to you.';
                    // find the old activity ...
                    $activity = DB::findOneBy(Activity::class, [
                        'actor_id'    => $user->getId(),
                        'verb'        => 'offer',
                        'object_type' => 'webmonetization',
                        'object_id'   => $entry->getId(),
                    ], order_by: ['created' => 'DESC']);
                    // ... and undo it
                    $activity = Activity::create([
                        'actor_id'    => $user->getId(),
                        'verb'        => 'undo',
                        'object_type' => 'activity',
                        'object_id'   => $activity->getId(),
                        'source'      => 'web',
                    ]);
                }
                DB::persist($activity);
                Event::handle('NewNotification', [
                    $user,
                    $activity,
                    ['object' => [$receiver_id]],
                    _m($message, ['{nickname}' => $user->getNickname()]),
                ]);
                DB::flush();
                // --

                throw new RedirectException();
            }
        }
        return Event::next;
    }

    public static function cacheKeys(int|LocalUser|Actor $id): array
    {
        if (!\is_int($id)) {
            $id = $id->getId();
        }
        return [
            'wallets' => "webmonetization-wallets-sender-{$id}",
        ];
    }

    public function onAppendToHead(Request $request, &$res): bool
    {
        $user = Common::user();
        if (\is_null($user)) {
            return Event::next;
        }

        // donate to everyone!
        // Using Javascript, it can be improved to donate only
        // to actors owning notes rendered on current page.
        $entries = Cache::getList(
            self::cacheKeys($user->getId())['wallets'],
            fn () => DB::dql(
                <<<'EOF'
                    SELECT wallet FROM webmonetizationWallet wallet
                        INNER JOIN webmonetization wm
                        WITH wallet.actor_id = wm.receiver
                    WHERE wm.active = :active AND wm.sender = :sender
                    EOF,
                ['sender' => $user->getId(), 'active' => true],
            ),
        );

        foreach ($entries as $entry) {
            $res[] = Formatting::twigRenderString(
                '<meta name="monetization" content="{{ address }}">',
                ['address' => $entry->getAddress()],
            );
        }
        return Event::next;
    }
}
