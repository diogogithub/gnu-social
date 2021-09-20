<?php

namespace Component\Tag\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Core\DB\DB;
use App\Util\Common;
use Component\Tag\Tag as CompTag;

class Tag extends Controller
{
    public function tag(string $tag)
    {
        // TODO scope
        $per_page = Common::config('streams', 'notes_per_page');
        $page     = $this->int('page') ?: 1;
        $tag      = CompTag::canonicalTag($tag);
        $notes    = array_reverse( // TODO meme
            Cache::getList(
                "tag-{$tag}",
                fn () => DB::dql(
                    'select n from note n join note_tag nt with nt.note_id = n.id ' .
                    'where nt.canonical = :tag order by nt.created ASC, n.id ASC',
                    ['tag' => $tag]),
                offset: $per_page * ($page - 1),
                limit: $per_page - 1
            )
        );

        return [
            '_template' => 'tag_stream.html.twig',
            'notes'     => $notes,
            'page'      => $page,
        ];
    }
}
