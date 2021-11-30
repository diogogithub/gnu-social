<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Model\EntityToType;

use App\Core\Entity;
use Exception;
use Plugin\ActivityPub\Util\Type;

abstract class EntityToType
{
    /**
     * @return Type
     * @throws Exception
     */
    public static function translate(Entity $entity): mixed
    {
        switch ($entity::class) {
            case 'App\Entity\Activity':
                return ActivityToType::translate($entity);
            case 'App\Entity\Note':
                return NoteToType::translate($entity);
            default:
                $map = [
                    'type' => 'Object',
                ];
                return Type::create($map);
        }
    }
}
