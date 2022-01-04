<?php

declare(strict_types = 1);

namespace Component\Circle\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Entity as E;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\RedirectException;
use Component\Circle\Entity\ActorCircle;
use Component\Circle\Entity\ActorTag;
use Component\Circle\Form\SelfTagsForm;
use Component\Tag\Tag as CompTag;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;

class SelfTagsSettings extends Controller
{
    /**
     * Generic settings page for an Actor's self tags
     */
    public static function settingsSelfTags(Request $request, E\Actor $target, string $details_id)
    {
        $actor = Common::actor();
        if (!$actor->canAdmin($target)) {
            throw new ClientException(_m('You don\'t have enough permissions to edit {nickname}\'s settings', ['{nickname}' => $target->getNickname()]));
        }

        $actor_self_tags            = $target->getSelfTags();
        [$add_form, $existing_form] = SelfTagsForm::handleTags(
            $request,
            $actor_self_tags,
            handle_new: /**
             * Handle adding tags
             */
            function ($form) use ($request, $target, $details_id) {
                $data = $form->getData();
                $tags = $data['new-tags'];
                foreach ($tags as $tag) {
                    $tag = CompTag::sanitize($tag);

                    [$actor_tag, $actor_tag_existed] = ActorTag::createOrUpdate([
                        'tagger' => $target->getId(), // self tag means tagger = tagger in ActorTag
                        'tagged' => $target->getId(),
                        'tag'    => $tag,
                    ]);
                    if (!$actor_tag_existed) {
                        DB::persist($actor_tag);
                        // Try to find the self-tag circle
                        $actor_circle = DB::findOneBy(
                            ActorCircle::class,
                            [
                                'tagger' => null, // Self-tag circle
                                'tag'    => $tag,
                            ],
                            return_null: true,
                        );
                        // It is the first time someone uses this self-tag!
                        if (\is_null($actor_circle)) {
                            DB::persist(ActorCircle::create([
                                'tagger'      => null, // Self-tag circle
                                'tag'         => $tag,
                                'private'     => false, // by definition
                                'description' => null, // The controller can show this in every language as appropriate
                            ]));
                        }
                    }
                }
                DB::flush();
                Cache::delete(E\Actor::cacheKeys($target->getId())['self-tags']);
                throw new RedirectException($request->get('_route'), ['nickname' => $target->getNickname(), 'open' => $details_id, '_fragment' => $details_id]);
            },
            handle_existing: /**
             * Handle changes to the existing tags
             */
            function ($form, array $form_definition) use ($request, $target, $details_id) {
                $changed = false;
                foreach (array_chunk($form_definition, 2) as $entry) {
                    $tag = CompTag::sanitize($entry[0][2]['data']);

                    /** @var SubmitButton $remove */
                    $remove = $form->get($entry[1][0]);
                    if ($remove->isClicked()) {
                        $changed = true;
                        DB::removeBy(
                            'actor_tag',
                            [
                                'tagger' => $target->getId(),
                                'tagged' => $target->getId(),
                                'tag'    => $tag,
                            ],
                        );
                        // We intentionally leave the self-tag actor circle, even if it is now empty
                    }
                }
                if ($changed) {
                    DB::flush();
                    Cache::delete(E\Actor::cacheKeys($target->getId())['self-tags']);
                    throw new RedirectException($request->get('_route'), ['nickname' => $target->getNickname(), 'open' => $details_id, '_fragment' => $details_id]);
                }
            },
            remove_label: _m('Remove self tag'),
            add_label: _m('Add self tag'),
        );

        return [
            '_template'               => 'self_tags_settings.fragment.html.twig',
            'add_self_tags_form'      => $add_form->createView(),
            'existing_self_tags_form' => $existing_form?->createView(),
        ];
    }
}
