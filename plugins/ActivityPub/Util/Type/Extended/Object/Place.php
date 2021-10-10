<?php

declare(strict_types = 1);

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityPub\Util\Type\Extended\Object;

use Plugin\ActivityPub\Util\Type\Core\ObjectType;

/**
 * \Plugin\ActivityPub\Util\Type\Extended\Object\Place is an implementation of
 * one of the Activity Streams Extended Types.
 *
 * Represents a logical or physical location.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-place
 */
class Place extends ObjectType
{
    protected string $type = 'Place';

    /**
     * Indicates the accuracy of position coordinates on a Place
     * objects. Expressed in properties of percentage.
     * e.g. "94.0" means "94.0% accurate".
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-accuracy
     */
    protected ?float $accuracy;

    /**
     * The altitude of a place.
     * The measurement units is indicated using the units' property.
     * If units is not specified, the default is assumed to be "m"
     * indicating meters.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-altitude
     */
    protected ?float $altitude;

    /**
     * The latitude of a place.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-latitude
     */
    protected int|null|float $latitude;

    /**
     * The longitude of a place.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-longitude
     */
    protected int|null|float $longitude;

    /**
     * The radius from the given latitude and longitude for a Place.
     * The units are expressed by the units' property.
     * If units is not specified, the default is assumed to be "m"
     * indicating "meters".
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-radius
     */
    protected int|null|float $radius;

    /**
     * Specifies the measurement units for the radius and altitude
     * properties on a Place object.
     * If not specified, the default is assumed to be "m" for "meters".
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-units
     *
     * "cm" | " feet" | " inches" | " km" | " m" | " miles" | xsd:anyURI
     */
    protected string $units;
}
