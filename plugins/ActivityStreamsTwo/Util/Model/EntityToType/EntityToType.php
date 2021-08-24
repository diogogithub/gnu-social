<?php

namespace Plugin\ActivityStreamsTwo\Util\Model\EntityToType;

use Plugin\ActivityStreamsTwo\Util\Type;

abstract class EntityToType
{
    /**
     * @param $entity
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