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
 * String formatting utilities
 *
 * @package   GNUsocial
 * @category  Util
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util;

use Stringable;

abstract class Functional
{
    /**
     * TODO replace with \Functional\cartesian_product when it gets merged upstream
     *
     * @param array<array<string|Stringable>> $collections
     */
    public static function cartesianProduct(array $collections, string|array $separator = ''): array
    {
        $aggregation = [];
        $left        = array_shift($collections);
        while (true) {
            $right = array_shift($collections);
            foreach ($left as $l) {
                foreach ($right as $r) {
                    if (\is_string($separator)) {
                        $aggregation[] = "{$l}{$separator}{$r}";
                    } elseif (\is_array($separator)) {
                        foreach ($separator as $sep) {
                            $aggregation[] = "{$l}{$sep}{$r}";
                        }
                    }
                }
            }
            if (empty($collections)) {
                break;
            } else {
                $left        = $aggregation;
                $aggregation = [];
            }
        }

        return $aggregation;
    }
}
