<?php

namespace Component\Tag\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Util\Common;
use Component\Tag\Tag as CompTag;

class Tag extends Controller
{
    public function tag(string $tag)
    {
        $user      = Common::user();
        $page      = $this->int('page') ?: 1;
        $canonical = CompTag::canonicalTag($tag);
        $notes     = Cache::pagedStream(
            key: "tag-{$canonical}",
            query: 'select n from note n join note_tag nt with n.id = nt.note_id where nt.canonical = :canon order by nt.created DESC, nt.note_id DESC',
            query_args: ['canon' => $canonical],
            actor: $user,
            page: $page
        );

        return [
            '_template' => 'tag_stream.html.twig',
            'notes'     => $notes,
            'page'      => $page,
        ];
    }
}
