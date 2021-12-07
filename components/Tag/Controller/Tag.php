<?php

declare(strict_types = 1);

namespace Component\Tag\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Util\Common;
use Component\Tag\Tag as CompTag;

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
}
