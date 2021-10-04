<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace Plugin\ActivityPub\Util\Type\Validator;

use Exception;
use Plugin\ActivityPub\Util\Type\Extended\AbstractActor;
use Plugin\ActivityPub\Util\Type\Util;
use Plugin\ActivityPub\Util\Type\ValidatorInterface;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\EndpointsValidator is a dedicated
 * validator for endpoints attribute.
 */
class EndpointsValidator implements ValidatorInterface
{
    /**
     * Validate ENDPOINTS value
     *
     * @param mixed $value
     * @param mixed $container
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validate(mixed $value, mixed $container): bool
    {
        // Validate that container is an AbstractActor type
        Util::subclassOf($container, AbstractActor::class, true);

        // A link to a JSON-LD document
        if (Util::validateUrl($value)) {
            return true;
        }

        // A map
        return is_array($value) && $this->validateObject($value);
    }

    /**
     * Validate endpoints mapping
     */
    protected function validateObject(array $item): bool
    {
        foreach ($item as $key => $value) {
            switch ($key) {
                case 'proxyUrl':
                case 'oauthAuthorizationEndpoint':
                case 'oauthTokenEndpoint':
                case 'provideClientKey':
                case 'signClientKey':
                case 'sharedInbox':
                    if (!Util::validateUrl($value)) {
                        return false;
                    }
                    break;
                // All other keys are not allowed
                default:
                    return false;
            }

            if (is_numeric($key)) {
                return false;
            }
        }

        return true;
    }
}
