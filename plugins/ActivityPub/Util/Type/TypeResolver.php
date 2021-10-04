<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityPub\Util\Type;

use Exception;

/**
 * \Plugin\ActivityPub\Util\Type\TypeResolver is an abstract class for
 * resolving class names called by their short names (AS types).
 */
abstract class TypeResolver
{
    /**
     * A list of core types
     *
     * @var array
     */
    protected static array $coreTypes = [
        'Activity', 'Collection', 'CollectionPage',
        'IntransitiveActivity', 'Link', 'ObjectType',
        'OrderedCollection', 'OrderedCollectionPage',
        'Object',
    ];

    /**
     * A list of actor types
     *
     * @var array
     */
    protected static array $actorTypes = [
        'Application', 'Group', 'Organization', 'Person', 'Service',
    ];

    /**
     * A list of activity types
     *
     * @var array
     */
    protected static array $activityTypes = [
        'Accept', 'Add', 'Announce', 'Block',
        'Create', 'Delete', 'Follow', 'Ignore',
        'Invite', 'Join', 'Leave', 'Like',
        'Question', 'Reject', 'Remove', 'Undo',
    ];

    /**
     * A list of object types
     *
     * @var array
     */
    protected static array $objectTypes = [
        'Article', 'Audio', 'Document', 'Event', 'Image',
        'Mention', 'Note', 'Page', 'Place', 'Profile',
        'Tombstone', 'Video',
    ];

    /**
     * Get namespaced class for a given short type
     *
     * @param string $type
     *
     * @throws Exception
     *
     * @return string Related namespace
     * @throw  \Exception if a namespace was not found.
     */
    public static function getClass(string $type): string
    {
        $ns = __NAMESPACE__;

        if ($type == 'Object') {
            $type .= 'Type';
        }

        switch ($type) {
            case in_array($type, self::$coreTypes):
                $ns .= '\Core';
                break;
            case in_array($type, self::$activityTypes):
                $ns .= '\Extended\Activity';
                break;
            case in_array($type, self::$actorTypes):
                $ns .= '\Extended\Actor';
                break;
            case in_array($type, self::$objectTypes):
                $ns .= '\Extended\Object';
                break;
            default:
                throw new Exception(
                    "Undefined scope for type '{$type}'"
                );
        }

        return $ns . '\\' . $type;
    }

    /**
     * Validate an object pool type with type attribute
     *
     * @param object $item
     * @param string $poolname An expected pool name
     *
     * @return bool
     */
    public static function isScope(object $item, string $poolname = 'all'): bool
    {
        if (!is_object($item)
            || !isset($item->type)
            || !is_string($item->type)
        ) {
            return false;
        }

        return match (strtolower($poolname)) {
            'all'   => self::exists($item->type),
            'actor' => in_array($item->type, self::$actorTypes),
            default => false,
        };
    }

    /**
     * Verify that a type exists
     *
     * @param string $name
     *
     * @return bool
     */
    public static function exists(string $name): bool
    {
        return in_array(
            $name,
            array_merge(
                self::$coreTypes,
                self::$activityTypes,
                self::$actorTypes,
                self::$objectTypes
            )
        );
    }
}
