<?php

declare(strict_types = 1);

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

/**
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @category  ActivityPub
 *
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Util;

use ActivityPhp\Type;
use ActivityPhp\Type\TypeConfiguration;
use ActivityPhp\Type\TypeResolver;
use App\Core\Entity;
use App\Core\Event;
use App\Util\Exception\ClientException;
use Exception;
use InvalidArgumentException;
use Plugin\ActivityPub\Util\Model\Activity;
use Plugin\ActivityPub\Util\Model\Note;

/**
 * This class handles translation between JSON and GS Entities
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
abstract class Model
{
    /**
     * Create a Type from an ActivityStreams 2.0 JSON string
     *
     * @throws Exception
     */
    public static function jsonToType(string|array $data): Type\AbstractObject
    {
        if (\is_string($data)) {
            $attributes = json_decode($data, true);
            if (json_last_error() !== \JSON_ERROR_NONE
                || !\is_array($attributes)
            ) {
                throw new Exception(
                    sprintf(
                        "An error occurred during the JSON decoding.\n '%s'",
                        $data,
                    ),
                );
            }
        } else {
            $attributes = $data;
        }

        if (!\array_key_exists('type', $attributes)) {
            throw new InvalidArgumentException('Missing "type" attribute in $data: ' . var_export($data, true));
        }
        unset($data);

        try {
            $type = TypeResolver::getClass($attributes['type']);
        } catch (Exception $e) {
            $message = json_encode($attributes, \JSON_PRETTY_PRINT);
            throw new Exception(
                $e->getMessage() . "\n{$message}",
            );
        }

        if (\is_string($type)) {
            $type = new $type();
        }

        // Add our own extensions
        $validators = [];
        if (Event::handle('ActivityPubValidateActivityStreamsTwoData', [$attributes['type'], &$validators]) === Event::next) {
            foreach ($validators as $name => $class) {
                Type::addValidator($name, $class);
                $type->extend($name);
            }
        }

        TypeConfiguration::set('undefined_properties', 'include');
        foreach ($attributes as $name => $value) {
            $type->set($name, $value);
        }

        return $type;
    }

    /**
     * Create an Entity from an ActivityStreams 2.0 JSON string
     */
    abstract public static function fromJson(string|Type\AbstractObject $json, array $options = []): Entity;

    /**
     * Get a JSON
     *
     * @param ?int $options PHP JSON options
     *
     * @throws ClientException
     */
    public static function toJson(mixed $object, ?int $options = null): string
    {
        switch ($object::class) {
            case \App\Entity\Activity::class:
                return Activity::toJson($object, $options);
            case \App\Entity\Note::class:
                return Note::toJson($object, $options);
            default:
                $type = self::jsonToType($object);
                Event::handle('ActivityPubAddActivityStreamsTwoData', [$type->get('type'), &$type]);
                return $type->toJson($options);
        }
    }
}
