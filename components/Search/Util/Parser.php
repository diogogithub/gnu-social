<?php

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

namespace Component\Search\Util;

use App\Core\Event;
use App\Util\Exception\ServerException;
use Doctrine\Common\Collections\Criteria;

abstract class Parser
{
    /**
     * Parse $input string into a Doctrine query Criteria
     *
     * Currently doesn't support nesting with parenthesis and
     * recognises either spaces (currently `or`, should be fuzzy match), `OR` or `|` (`or`) and `AND` or `&` (`and`)
     *
     * TODO Better fuzzy match, implement exact match with quotes and nesting with parens
     */
    public static function parse(string $input, int $level = 0): Criteria
    {
        if ($level === 0) {
            $input = trim(preg_replace(['/\s+/', '/\s+AND\s+/', '/\s+OR\s+/'], [' ', '&', '|'], $input), ' |&');
        }

        $left     = $right     = 0;
        $lenght   = mb_strlen($input);
        $stack    = [];
        $eb       = Criteria::expr();
        $criteria = [];
        $parts    = [];
        $last_op  = null;

        $connect_parts = /**
                        * Merge $parts into $criteria
                        */
                       function (bool $force = false) use ($eb, &$parts, $last_op, &$criteria) {
                           foreach ([' ' => 'orX', '|' => 'orX', '&' => 'andX'] as $op => $func) {
                               if ($last_op === $op || $force) {
                                   $criteria[] = $eb->{$func}(...$parts);
                                   $parts      = [];
                                   break;
                               }
                           }
                       };

        for ($index = 0; $index < $lenght; ++$index) {
            $end   = false;
            $match = false;

            foreach (['&', '|', ' '] as $delimiter) {
                if ($input[$index] === $delimiter || $end = ($index === $lenght - 1)) {
                    $term = substr($input, $left, $end ? null : $right - $left);
                    $res  = null;
                    $ret  = Event::handle('SearchCreateExpression', [$eb, $term, &$res]);
                    if (is_null($res) || $ret == Event::next) {
                        throw new ServerException("No one claimed responsibility for a match term: {$term}");
                    }
                    $parts[] = $res;

                    $right = $left = $index + 1;

                    if (!is_null($last_op) && $last_op !== $delimiter) {
                        $connect_parts(force: false);
                    } else {
                        $last_op = $delimiter;
                    }
                    $match = true;
                    continue 2;
                }
            }
            if (!$match) {
                ++$right;
            }
        }

        if (!empty($parts)) {
            $connect_parts(force: true);
        }

        return new Criteria($eb->orX(...$criteria));
    }
}
