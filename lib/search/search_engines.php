<?php
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

defined('GNUSOCIAL') || die();

class SearchEngine
{
    protected $target;
    protected $table;

    public function __construct($target, $table)
    {
        $this->target = $target;
        $this->table = $table;
    }

    public function query($q)
    {
    }

    public function limit($offset, $count, $rss = false)
    {
        return $this->target->limit($offset, $count);
    }

    public function set_sort_mode($mode)
    {
        switch ($mode) {
            case 'chron':
                return $this->target->orderBy('created DESC');
                break;
            case 'reverse_chron':
                return $this->target->orderBy('created ASC');
                break;
            case 'nickname_desc':
                if ($this->table != 'profile') {
                    throw new Exception(
                        'nickname_desc sort mode can only be use when searching profile.'
                    );
                } else {
                    return $this->target->orderBy(sprintf('%1$s.nickname DESC', $this->table));
                }
                break;
            case 'nickname_asc':
                if ($this->table != 'profile') {
                    throw new Exception(
                        'nickname_desc sort mode can only be use when searching profile.'
                    );
                } else {
                    return $this->target->orderBy(sprintf('%1$s.nickname ASC', $this->table));
                }
                break;
            default:
                return $this->target->orderBy('created DESC');
                break;
        }
    }
}

class PostgreSQLSearch extends SearchEngine
{
    public function query($q)
    {
        if ($this->table === 'profile') {
            $cols = implode(" || ' ' || ", array_map(
                function ($col) {
                    return sprintf(
                        'COALESCE(%s."%s", \'\')',
                        common_database_tablename($this->table),
                        $col
                    );
                },
                ['nickname', 'fullname', 'location', 'bio', 'homepage']
            ));

            $this->target->whereAdd(sprintf(
                'to_tsvector(\'english\', %2$s) @@ websearch_to_tsquery(\'%1$s\')',
                $this->target->escape($q, true),
                $cols
            ));
            return true;
        } elseif ($this->table === 'notice') {
            // Don't show direct messages.
            $this->target->whereAdd('notice.scope <> ' . Notice::MESSAGE_SCOPE);
            // Don't show imported notices
            $this->target->whereAdd('notice.is_local <> ' . Notice::GATEWAY);

            $this->target->whereAdd(sprintf(
                'to_tsvector(\'english\', "content") @@ websearch_to_tsquery(\'%1$s\')',
                $this->target->escape($q, true)
            ));
            return true;
        } else {
            throw new ServerException('Unknown table: ' . $this->table);
        }
    }
}

class MySQLSearch extends SearchEngine
{
    /*
     * Creates a full-text MATCH IN BOOLEAN MODE from the query format
     * analogous to PostgreSQL's websearch_to_tsquery.
     * The resulting boolean search query should never raise syntax errors
     * regardless of the kind of input this method receives.
     *
     * The syntax is as follows:
     *  - unquoted text: text not inside quote marks will be converted to
     *    individual quoted words with "+" operators each.
     *  - "quoted text": text inside quote marks will have the "+" operator
     *    prepended.
     *  - OR: causes the two adjoined words to lose the "+" operator.
     *  - "-": words prepended with the "-" operator will retain it unquoted.
     */
    private function websearchToBoolean(string $input): string
    {
        $split = [];
        preg_match_all('/(?:[^\s"]|["][^"]*["])+/', $input, $split);

        $phrases = [];
        $or_cond = false;
        foreach ($split[0] as $phrase) {
            if (strtoupper($phrase) === 'OR') {
                $last = &$phrases[array_key_last($phrases)];
                $last['op'] = '';
                $or_cond = true;
                continue;
            }

            if (substr($phrase, 0, 1) === '-') {
                $phrases[] = ['op' => '-', 'text' => substr($phrase, 1)];
            } elseif ($or_cond) {
                $phrases[] = ['op' => '',  'text' => $phrase];
            } else {
                $phrases[] = ['op' => '+', 'text' => $phrase];
            }
            $or_cond = false;
        }

        return array_reduce(
            $phrases,
            function (string $carry, array $item): string {
                // Strip all double quote marks and wrap with them around
                $text = '"' . str_replace('"', '', $item['text']) . '"';

                return $carry . ' ' . $item['op'] . $text;
            },
            ''
        );
    }

    public function query($q)
    {
        if ($this->table === 'profile') {
            $tables = sprintf(
                '%1$s.nickname, %1$s.fullname, %1$s.location, %1$s.bio, %1$s.homepage',
                $this->table
            );
        } elseif ($this->table === 'notice') {
            // Don't show direct messages.
            $this->target->whereAdd('notice.scope <> ' . Notice::MESSAGE_SCOPE);
            // Don't show imported notices
            $this->target->whereAdd('notice.is_local <> ' . Notice::GATEWAY);

            $tables = 'notice.content';
        } else {
            throw new ServerException('Unknown table: ' . $this->table);
        }

        $boolean_query = $this->websearchToBoolean($q);

        $this->target->whereAdd(sprintf(
            'MATCH (%1$s) AGAINST (\'%2$s\' IN BOOLEAN MODE)',
            $tables,
            $this->target->escape($boolean_query)
        ));

        return true;
    }
}

class SQLLikeSearch extends SearchEngine
{
    public function query($q)
    {
        if ($this->table === 'profile') {
            $qry = sprintf(
                '(   %2$s.nickname LIKE \'%%%1$s%%\' ' .
                ' OR %2$s.fullname LIKE \'%%%1$s%%\' ' .
                ' OR %2$s.location LIKE \'%%%1$s%%\' ' .
                ' OR %2$s.bio      LIKE \'%%%1$s%%\' ' .
                ' OR %2$s.homepage LIKE \'%%%1$s%%\')',
                $this->target->escape($q, true),
                $this->table
            );
        } elseif ($this->table === 'notice') {
            // Don't show direct messages.
            $this->target->whereAdd('notice.scope <> ' . Notice::MESSAGE_SCOPE);
            // Don't show imported notices
            $this->target->whereAdd('notice.is_local <> ' . Notice::GATEWAY);

            $qry = sprintf(
                'notice.content LIKE \'%%%1$s%%\'',
                $this->target->escape($q, true)
            );
        } else {
            throw new ServerException('Unknown table: ' . $this->table);
        }

        $this->target->whereAdd($qry);

        return true;
    }
}
