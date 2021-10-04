<?php

namespace Plugin\ActivityPub\Util\Model\EntityToType;

use App\Core\Entity;
use Plugin\ActivityPub\Util\Type;

abstract class EntityToType
{
    /**
     * @param Entity $entity
     *
     * @throws \Exception
     *
     * @return Type
     */
    public static function translate($entity)
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
