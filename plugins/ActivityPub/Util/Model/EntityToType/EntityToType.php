<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub\Util\Model\EntityToType;

use App\Core\Entity;
use Exception;
use Plugin\ActivityPub\Util\Type;

abstract class EntityToType
{
    /**
     * @throws Exception
     */
    public static function translate(Entity $entity): Type
    {
        switch ($entity::class) {
            case 'Note':
                return NoteToType::translate($entity);
            default:
                $map = [
                    'type' => 'Object',
                ];
                return Type::create($map);
        }
    }
}
