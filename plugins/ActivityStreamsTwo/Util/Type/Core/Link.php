<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityStreamsTwo\Util\Type\Core;

use Plugin\ActivityStreamsTwo\Util\Type\AbstractObject;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Core\Link is an implementation of one of the
 * Activity Streams Core Types.
 *
 * A Link describes a qualified, indirect reference to another resource.
 * The properties of the Link object are not the properties of the
 * referenced resource, but are provided as hints for rendering agents
 * to understand how to make use of the resource.
 *
 * @see https://www.w3.org/TR/activitystreams-core/#link
 */
class Link extends AbstractObject
{
    /**
     * @var string
     */
    protected string $type = 'Link';

    /**
     * @var string
     */
    protected string $id;

    /**
     * A simple, human-readable, plain-text name for the object.
     * HTML markup MUST NOT be included.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-name
     *
     * @var null|string xsd:string
     */
    protected ?string $name;

    /**
     * The name MAY be expressed using multiple language-tagged values.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-name
     *
     * @var null|array<string,string> rdf:langString
     */
    protected ?array $nameMap;

    /**
     * The target resource pointed to by a Link.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-href
     *
     * @var null|string
     */
    protected ?string $href;

    /**
     * Hints as to the language used by the target resource.
     * Value MUST be a BCP47 Language-Tag.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-hreflang
     *
     * @var null|string
     */
    protected ?string $hreflang;

    /**
     * The MIME media type of the referenced resource.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-mediatype
     *
     * @var null|string
     */
    protected ?string $mediaType;

    /**
     * A link relation associated with a Link.
     * The value MUST conform to both the HTML5
     * and RFC5988 "link relation" definitions.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-rel
     *
     * @var null|array|string
     */
    protected string|array|null $rel;

    /**
     * Specifies a hint as to the rendering height
     * in device-independentpixels of the linked resource
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-height
     *
     * @var null|int A non negative integer
     */
    protected ?int $height;

    /**
     * An entity that provides a preview of this link.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-preview
     *
     * @var string
     *             | Object
     *             | Link
     *             | null
     */
    protected string $preview;

    /**
     * On a Link, specifies a hint as to the rendering width in
     * device-independent pixels of the linked resource.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-width
     *
     * @var null|int A non negative integer
     */
    protected ?int $width;
}
