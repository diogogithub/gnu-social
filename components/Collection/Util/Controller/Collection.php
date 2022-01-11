<?php

declare(strict_types = 1);

namespace Component\Collection\Util\Controller;

use App\Core\Controller;
use App\Entity\Actor;
use App\Util\Common;
use Component\Collection\Collection as CollectionModule;

class Collection extends Controller
{
    public function query(string $query, ?string $locale = null, ?Actor $actor = null): array
    {
        $actor  ??= Common::actor();
        $locale ??= Common::currentLanguage()->getLocale();
        return CollectionModule::query($query, $this->int('page') ?? 1, $locale, $actor);
    }
}
