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

namespace Component\Search\Util;

use App\Core\Event;
use App\Util\Exception\ServerException;
use Doctrine\Common\Collections\Criteria;

abstract class Parser
{
    /**
     * Merge $parts into $criteria_arr
     */
    private static function connectParts(array &$parts, array &$criteria_arr, string $last_op, mixed $eb, bool $force = false): void
    {
        foreach ([' ' => 'orX', '|' => 'orX', '&' => 'andX'] as $op => $func) {
            if ($last_op === $op || $force) {
                $criteria_arr[] = $eb->{$func}(...$parts);
                $note_parts     = [];
                break;
            }
        }
    }

    /**
     * Parse $input string into a Doctrine query Criteria
     *
     * Currently doesn't support nesting with parenthesis and
     * recognises either spaces (currently `or`, should be fuzzy match), `OR` or `|` (`or`) and `AND` or `&` (`and`)
     *
     * TODO Better fuzzy match, implement exact match with quotes and nesting with parens
     *
     * @return Criteria[]
     */
    public static function parse(string $input, int $level = 0): array
    {
        if ($level === 0) {
            $input = trim(preg_replace(['/\s+/', '/\s+AND\s+/', '/\s+OR\s+/'], [' ', '&', '|'], $input), ' |&');
        }

        $left               = $right               = 0;
        $lenght             = mb_strlen($input);
        $stack              = [];
        $eb                 = Criteria::expr();
        $note_criteria_arr  = [];
        $actor_criteria_arr = [];
        $note_parts         = [];
        $actor_parts        = [];
        $last_op            = null;

        for ($index = 0; $index < $lenght; ++$index) {
            $end   = false;
            $match = false;

            foreach (['&', '|', ' '] as $delimiter) {
                if ($input[$index] === $delimiter || $end = ($index === $lenght - 1)) {
                    $term     = mb_substr($input, $left, $end ? null : $right - $left);
                    $note_res = $actor_res = null;
                    $ret      = Event::handle('SearchCreateExpression', [$eb, $term, &$note_res, &$actor_res]);
                    if ((\is_null($note_res) && \is_null($actor_res)) || $ret == Event::next) {
                        throw new ServerException("No one claimed responsibility for a match term: {$term}");
                    } elseif (!\is_null($note_res)) {
                        $note_parts[] = $note_res;
                    } elseif (!\is_null($actor_res)) {
                        $actor_parts[] = $actor_res;
                    } else {
                        throw new ServerException('Unexpected state in Search parser');
                    }

                    $right = $left = $index + 1;

                    if (!\is_null($last_op) && $last_op !== $delimiter) {
                        self::connectParts($note_parts, $note_criteria_arr, $last_op, $eb, force: false);
                        self::connectParts($actor_parts, $actor_criteria_arr, $last_op, $eb, force: false);
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

        $note_criteria = $actor_criteria = null;
        if (!empty($note_parts)) {
            self::connectParts($note_parts, $note_criteria_arr, $last_op, $eb, force: true);
            $note_criteria = new Criteria($eb->orX(...$note_criteria_arr));
        } elseif (!empty($actor_parts)) {
            self::connectParts($actor_parts, $actor_criteria_arr, $last_op, $eb, force: true);
            $actor_criteria = new Criteria($eb->orX(...$actor_criteria_arr));
        }

        return [$note_criteria, $actor_criteria];
    }
}
