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

namespace Plugin\ActivityPub\Util\Type\Validator;

use Exception;
use Plugin\ActivityPub\Util\Type\ValidatorTools;

/**
 * \Plugin\ActivityPub\Util\Type\Validator\SummaryMapValidator is a dedicated
 * validator for summaryMap attribute.
 */
class SummaryMapValidator extends ValidatorTools
{
    /**
     * Validate a summaryMap attribute value
     *
     * @param mixed $container An Object type
     *
     * @throws Exception
     */
    public function validate(mixed $value, mixed $container): bool
    {
        return $this->validateMap('summary', $value, $container);
    }
}
