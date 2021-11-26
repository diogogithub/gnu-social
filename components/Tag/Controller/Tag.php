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
    private function process(string|array $tag_or_tags, callable $key, string $query)
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
        $notes = Cache::pagedStream(
            key: $key($canonical),
            query: $query,
            query_args: ['canon' => $canonical],
            actor: $actor,
            page: $page,
        );

        return [
            '_template' => 'tag_stream.html.twig',
            'notes'     => $notes,
            'page'      => $page,
        ];
    }

    public function single_tag(string $tag)
    {
        return $this->process(
            tag_or_tags: $tag,
            key: fn ($canonical) => "tag-{$canonical}",
            query: 'select n from note n join note_tag nt with n.id = nt.note_id where nt.canonical = :canon order by nt.created DESC, nt.note_id DESC',
        );
    }

    public function multi_tags(string $tags)
    {
        $tags = explode(',', $tags);
        return $this->process(
            tag_or_tags: $tags,
            key: fn ($canonical) => 'tags-' . implode('-', $canonical),
            query: 'select n from note n join note_tag nt with n.id = nt.note_id where nt.canonical in (:canon) order by nt.created DESC, nt.note_id DESC',
        );
    }
}
