<?php

declare(strict_types = 1);

namespace Component\Tag\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Util\Common;
use Component\Tag\Tag as CompTag;
use Functional as F;

class Tag extends Controller
{
    private function process(string|array $tag_or_tags, callable $key, string $query, string $template)
    {
        $actor = Common::actor();
        $page  = $this->int('page') ?: 1;
        $lang  = $this->string('lang');
        if (\is_null($lang)) {
            $langs = $actor->getPreferredLanguageChoices();
            $lang  = $langs[array_key_first($langs)];
        }
        if (\is_string($tag_or_tags)) {
            $canonical = CompTag::canonicalTag($tag_or_tags, $lang);
        } else {
            $canonical = F\map($tag_or_tags, fn ($t) => CompTag::canonicalTag($t, $lang));
        }
        $results = Cache::pagedStream(
            key: $key($canonical),
            query: $query,
            query_args: ['canon' => $canonical],
            actor: $actor,
            page: $page,
        );

        return [
            '_template' => $template,
            'results'   => $results,
            'page'      => $page,
        ];
    }

    public function single_note_tag(string $tag)
    {
        return $this->process(
            tag_or_tags: $tag,
            key: fn ($canonical) => "note-tag-feed-{$canonical}",
            query: 'select n from note n join note_tag nt with n.id = nt.note_id where nt.canonical = :canon order by nt.created DESC, nt.note_id DESC',
            template: 'note_tag_feed.html.twig',
        );
    }

    public function multi_note_tags(string $tags)
    {
        $tags = explode(',', $tags);
        return $this->process(
            tag_or_tags: $tags,
            key: fn ($canonical) => 'note-tags-feed-' . implode('-', $canonical),
            query: 'select n from note n join note_tag nt with n.id = nt.note_id where nt.canonical in (:canon) order by nt.created DESC, nt.note_id DESC',
            template: 'note_tag_feed.html.twig',
        );
    }

    public function single_actor_tag(string $tag)
    {
        return $this->process(
            tag_or_tags: $tag,
            key: fn ($canonical) => "actor-tag-feed-{$canonical}",
            query: 'select a from actor a join actor_tag at with a.id = at.tagged where at.canonical = :canon order by at.modified DESC',
            template: 'actor_tag_feed.html.twig',
        );
    }

    public function multi_actor_tag(string $tags)
    {
        $tags = explode(',', $tags);
        return $this->process(
            tag_or_tags: $tags,
            key: fn ($canonical) => 'actor-tags-feed-' . implode('-', $canonical),
            query: 'select a from actor a join actor_tag at with a.id = at.tagged where at.canonical = :canon order by at.modified DESC',
            template: 'actor_tag_feed.html.twig',
        );
    }
}
