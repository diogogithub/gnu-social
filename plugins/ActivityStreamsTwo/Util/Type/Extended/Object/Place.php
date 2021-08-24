<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityStreamsTwo\Util\Type\Extended\Object;

use Plugin\ActivityStreamsTwo\Util\Type\Core\ObjectType;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Extended\Object\Place is an implementation of
 * one of the Activity Streams Extended Types.
 *
 * Represents a logical or physical location.
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-place
 */
class Place extends ObjectType
{
    /**
     * @var string
     */
    protected string $type = 'Place';

    /**
     * Indicates the accuracy of position coordinates on a Place
     * objects. Expressed in properties of percentage.
     * e.g. "94.0" means "94.0% accurate".
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-accuracy
     *
     * @var null|float
     */
    protected ?float $accuracy;

    /**
     * The altitude of a place.
     * The measurement units is indicated using the units' property.
     * If units is not specified, the default is assumed to be "m"
     * indicating meters.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-altitude
     *
     * @var null|float
     */
    protected ?float $altitude;

    /**
     * The latitude of a place.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-latitude
     *
     * @var null|float|int
     */
    protected int|null|float $latitude;

    /**
     * The longitude of a place.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-longitude
     *
     * @var null|float|int
     */
    protected int|null|float $longitude;

    /**
     * The radius from the given latitude and longitude for a Place.
     * The units are expressed by the units' property.
     * If units is not specified, the default is assumed to be "m"
     * indicating "meters".
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-radius
     *
     * @var null|float|int
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
     *
     * @var string
     */
    protected string $units;
}
