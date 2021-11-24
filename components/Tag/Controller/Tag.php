<?php

declare(strict_types = 1);

namespace Component\Tag\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Util\Common;
use Component\Tag\Tag as CompTag;

class Tag extends Controller
{
    public function tag(string $tag)
    {
        $actor = Common::actor();
        $page  = $this->int('page') ?: 1;
        $lang  = $this->string('lang');
        if (\is_null($lang)) {
            $langs = $actor->getPreferredLanguageChoices();
            $lang  = $langs[array_key_first($langs)];
        }
        $canonical = CompTag::canonicalTag($tag, $lang);
        $notes     = Cache::pagedStream(
            key: "tag-{$canonical}",
            query: 'select n from note n join note_tag nt with n.id = nt.note_id where nt.canonical = :canon order by nt.created DESC, nt.note_id DESC',
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
}
