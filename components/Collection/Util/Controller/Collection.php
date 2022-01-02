<?php

declare(strict_types = 1);

namespace Component\Collection\Util\Controller;

use App\Core\Controller;
use Component\Feed\Feed;

class Collection extends Controller
{
    public function query(string $query, ?string $language = null, ?Actor $actor = null)
    {
        return Feed::query($query, $this->int('page') ?? 1, $language, $actor);
    }
}
