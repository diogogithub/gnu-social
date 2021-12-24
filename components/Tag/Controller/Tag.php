<?php

declare(strict_types = 1);

namespace Component\Tag\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Entity as E;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\RedirectException;
use App\Util\Formatting;
use Component\Tag\Form\SelfTagsForm;
use Component\Tag\Tag as CompTag;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;

class Tag extends Controller
{
    private function process(string|array $canon_single_or_multi, null|string|array $tag_single_or_multi, string $key, string $query, string $template)
    {
        $actor = Common::actor();
        $page  = $this->int('page') ?: 1;
        $lang  = $this->string('lang');

        $results = Cache::pagedStream(
            key: $key,
            query: $query,
            query_args: ['canon' => $canon_single_or_multi],
            actor: $actor,
            page: $page,
        );

        return [
            '_template' => $template,
            'tag_name'  => $tag_single_or_multi,
            'results'   => $results,
            'page'      => $page,
        ];
    }

    public function single_note_tag(string $canon)
    {
        return $this->process(
            canon_single_or_multi: $canon,
            tag_single_or_multi: $this->string('tag'),
            key: CompTag::cacheKeys($canon)['note_single'],
            query: 'select n from note n join note_tag nt with n.id = nt.note_id where nt.canonical = :canon order by nt.created DESC, nt.note_id DESC',
            template: 'note_tag_feed.html.twig',
        );
    }

    public function multi_note_tags(string $canons)
    {
        return $this->process(
            canon_single_or_multi: explode(',', $canons),
            tag_single_or_multi: !\is_null($this->string('tags')) ? explode(',', $this->string('tags')) : null,
            key: CompTag::cacheKeys(str_replace(',', '-', $canons))['note_multi'],
            query: 'select n from note n join note_tag nt with n.id = nt.note_id where nt.canonical in (:canon) order by nt.created DESC, nt.note_id DESC',
            template: 'note_tag_feed.html.twig',
        );
    }

    public function single_actor_tag(string $canon)
    {
        return $this->process(
            canon_single_or_multi: $canon,
            tag_single_or_multi: $this->string('tag'),
            key: CompTag::cacheKeys($canon)['actor_single'],
            query: 'select a from actor a join actor_tag at with a.id = at.tagged where at.canonical = :canon order by at.modified DESC',
            template: 'actor_tag_feed.html.twig',
        );
    }

    public function multi_actor_tag(string $canons)
    {
        return $this->process(
            canon_single_or_multi: explode(',', $canons),
            tag_single_or_multi: !\is_null($this->string('tags')) ? explode(',', $this->string('tags')) : null,
            key: CompTag::cacheKeys(str_replace(',', '-', $canons))['actor_multi'],
            query: 'select a from actor a join actor_tag at with a.id = at.tagged where at.canonical = :canon order by at.modified DESC',
            template: 'actor_tag_feed.html.twig',
        );
    }

    /**
     * Generic settings page for an Actor's self tags
     */
    public static function settingsSelfTags(Request $request, E\Actor $target, string $details_id)
    {
        $actor = Common::actor();
        if (!$actor->canAdmin($target)) {
            throw new ClientException(_m('You don\'t have enough permissions to edit {nickname}\'s settings', ['{nickname}' => $target->getNickname()]));
        }

        $actor_tags = $target->getSelfTags();

        [$add_form, $existing_form] = SelfTagsForm::handleTags(
            $request,
            $actor_tags,
            handle_new: /**
             * Handle adding tags
             */
            function ($form) use ($request, $target, $details_id) {
                $data = $form->getData();
                $tags = $data['new-tags'];
                $language = $target->getTopLanguage()->getLocale();
                foreach ($tags as $tag) {
                    $tag = CompTag::ensureValid($tag);
                    [$at, ] = E\ActorTag::createOrUpdate([
                        'tagger'        => $target->getId(),
                        'tagged'        => $target->getId(),
                        'tag'           => $tag,
                        'canonical'     => CompTag::canonicalTag($tag, language: $language),
                        'use_canonical' => $data['new-tags-use-canon'],
                    ]);
                    DB::persist($at);
                }
                DB::flush();
                Cache::delete(E\Actor::cacheKeys($target->getId(), $target->getId())['tags']);
                throw new RedirectException($request->get('_route'), ['nickname' => $target->getNickname(), 'open' => $details_id]);
            },
            handle_existing: /**
             * Handle changes to the existing tags
             */
            function ($form, array $form_definition) use ($request, $target, $details_id) {
                $data = $form->getData();
                $changed = false;
                foreach (array_chunk($form_definition, 3) as $entry) {
                    $tag = Formatting::removePrefix($entry[0][2]['data'], '#');
                    $use_canon = $entry[1][2]['attr']['data'];

                    /** @var SubmitButton $remove */
                    $remove = $form->get($entry[2][0]);
                    if ($remove->isClicked()) {
                        $changed = true;
                        DB::removeBy(
                            'actor_tag',
                            [
                                'tagger'        => $target->getId(),
                                'tagged'        => $target->getId(),
                                'tag'           => $tag,
                                'use_canonical' => $use_canon,
                            ],
                        );
                    }

                    /** @var SubmitButton $toggle_canon */
                    $toggle_canon = $form->get($entry[1][0]);
                    if ($toggle_canon->isSubmitted()) {
                        $changed = true;
                        $at = DB::find(
                            'actor_tag',
                            [
                                'tagger'        => $target->getId(),
                                'tagged'        => $target->getId(),
                                'tag'           => $tag,
                                'use_canonical' => $use_canon,
                            ],
                        );
                        DB::persist($at->setUseCanonical(!$use_canon));
                    }
                }
                if ($changed) {
                    DB::flush();
                    Cache::delete(E\Actor::cacheKeys($target->getId(), $target->getId())['tags']);
                    throw new RedirectException($request->get('_route'), ['nickname' => $target->getNickname(), 'open' => $details_id]);
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
