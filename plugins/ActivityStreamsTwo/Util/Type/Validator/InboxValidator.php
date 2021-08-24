<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityStreamsTwo\Util\Type\Validator;

use Exception;
use Plugin\ActivityStreamsTwo\Util\Type\Core\OrderedCollection;
use Plugin\ActivityStreamsTwo\Util\Type\Core\OrderedCollectionPage;
use Plugin\ActivityStreamsTwo\Util\Type\Extended\AbstractActor;
use Plugin\ActivityStreamsTwo\Util\Type\Util;
use Plugin\ActivityStreamsTwo\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityStreamsTwo\Util\Type\Validator\InboxValidator is a dedicated
 * validator for inbox attribute.
 */
class InboxValidator implements ValidatorInterface
{
    /**
     * Validate a inbox attribute value
     *
     * @param mixed $value
     * @param mixed $container
     *
     * @throws Exception
     *
     * @return bool
     *
     * @todo Support indirect reference for followers attribute?
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is an AbstractActor type
        Util::subclassOf($container, AbstractActor::class, true);

        if (is_string($value)) {
            return Util::validateUrl($value);
        }

        // An OrderedCollection
        return is_object($value) && $this->validateObject($value);
    }

    /**
     * Validate that it is an OrderedCollection
     *
     * @param object $collection
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function validateObject(object $collection): bool
    {
        return Util::subclassOf(
                $collection,
                OrderedCollection::class
            ) || Util::subclassOf(
                $collection,
                OrderedCollectionPage::class
            );
    }
}
