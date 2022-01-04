<?php

declare(strict_types = 1);

namespace Component\Collection\Util\Controller;

use App\Core\Controller;
use App\Entity\Actor;
use App\Util\Common;
use Component\Feed\Feed;

class Collection extends Controller
{
    public function query(string $query, ?string $locale = null, ?Actor $actor = null)
    {
        $actor  ??= Common::actor();
        $locale ??= Common::currentLanguage()->getLocale();
        return Feed::query($query, $this->int('page') ?? 1, $locale, $actor);
    }
}
