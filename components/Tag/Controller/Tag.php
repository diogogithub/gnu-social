<?php

namespace Component\Tag\Controller;

use App\Core\Cache;
use App\Core\Controller;
use Component\Tag\Tag as CompTag;

class Tag extends Controller
{
    public function tag(string $tag)
    {
        $page  = $this->int('page') ?: 1;
        $tag   = CompTag::canonicalTag($tag);
        $notes = Cache::pagedStream(
            key: "tag-{$tag}",
            query: 'select n from note n join note_tag nt with nt.note_id = n.id where nt.canonical = :tag order by nt.created DESC, n.id DESC',
            query_args: ['tag' => $tag],
            page: $page
        );

        return [
            '_template' => 'tag_stream.html.twig',
            'notes'     => $notes,
            'page'      => $page,
        ];
    }
}
