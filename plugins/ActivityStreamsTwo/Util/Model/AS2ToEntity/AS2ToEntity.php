<?php

namespace Plugin\ActivityStreamsTwo\Util\Model\AS2ToEntity;

use App\Core\Entity;

abstract class AS2ToEntity
{
    /**
     * @param array $activity
     *
     * @return Entity
     */
    public static function translate(array $activity, ?string $source = null): Entity
    {
        return match ($activity['type']) {
            'Note'  => AS2ToNote::translate($activity, $source),
            default => Entity::create($activity),
        };
    }
}