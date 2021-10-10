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

namespace Plugin\ActivityPub\Util\Type\Extended\Activity;

use Plugin\ActivityPub\Util\Type\Core\IntransitiveActivity;
use Plugin\ActivityPub\Util\Type\Core\ObjectType;

/**
 * \Plugin\ActivityPub\Util\Type\Extended\Activity\Question is an implementation of
 * one of the Activity Streams Extended Types.
 *
 * Represents a question being asked. Question objects are an extension
 * of IntransitiveActivity. That is, the Question object is an Activity,
 * but the direct object is the question itself, and therefore it would
 * not contain an object property.
 *
 * Either of the anyOf and oneOf properties MAY be used to express
 * possible answers, but a Question object MUST NOT have both properties
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-move
 */
class Question extends IntransitiveActivity
{
    protected string $type = 'Question';

    /**
     * An exclusive option for a Question
     * Use of oneOf implies that the Question can have only a
     * single answer.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-oneof
     *
     * @var array<ObjectType>
     *                        | array<\Plugin\ActivityPub\Util\Type\Core\Link>
     *                        | null
     */
    protected array $oneOf;

    /**
     * An inclusive option for a Question.
     * Use of anyOf implies that the Question can have multiple answers.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-anyof
     *
     * @var array<ObjectType>
     *                        | array<\Plugin\ActivityPub\Util\Type\Core\Link>
     *                        | null
     */
    protected array $anyOf;

    /**
     * Indicates that a question has been closed, and answers are no
     * longer accepted.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-closed
     *
     * @var ObjectType
     *                 | \Plugin\ActivityPub\Util\Type\Core\Link
     *                 | \DateTime
     *                 | bool
     *                 | null
     */
    protected ObjectType $closed;
}
