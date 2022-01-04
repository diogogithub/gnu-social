<?php

declare(strict_types = 1);

namespace Component\Tag\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Util\Common;
use Component\Language\Entity\Language;
use Component\Tag\Tag as CompTag;

class Tag extends Controller
{
    // TODO: Use Feed::query
    // TODO: If ?canonical=something, respect
    // TODO: Allow to set locale of tag being selected
    private function process(null|string|array $tag_single_or_multi, string $key, string $query, string $template, bool $include_locale = false)
    {
        $actor = Common::actor();
        $page  = $this->int('page') ?: 1;

        $query_args = ['tag' => $tag_single_or_multi];

        if ($include_locale) {
            if (!\is_null($locale = $this->string('locale'))) {
                $query_args['language_id'] = Language::getByLocale($locale)->getId();
            } else {
                $query_args['language_id'] = Common::actor()->getTopLanguage()->getId();
            }
        }

        $results = Cache::pagedStream(
            key: $key,
            query: $query,
            query_args: $query_args,
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

    public function single_note_tag(string $tag)
    {
        return $this->process(
            tag_single_or_multi: $tag,
            key: CompTag::cacheKeys($tag)['note_single'],
            query: 'SELECT n FROM note AS n JOIN note_tag AS nt WITH n.id = nt.note_id WHERE nt.tag = :tag AND nt.language_id = :language_id ORDER BY nt.created DESC, nt.note_id DESC',
            template: 'note_tag_feed.html.twig',
            include_locale: true,
        );
    }

    public function multi_note_tags(string $tags)
    {
        return $this->process(
            tag_single_or_multi: explode(',', $tags),
            key: CompTag::cacheKeys(str_replace(',', '-', $tags))['note_multi'],
            query: 'select n from note n join note_tag nt with n.id = nt.note_id where nt.tag in (:tag) AND nt.language_id = :language_id  order by nt.created DESC, nt.note_id DESC',
            template: 'note_tag_feed.html.twig',
            include_locale: true,
        );
    }
}
