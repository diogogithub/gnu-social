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
    public function query($q)
    {
        if ($this->table === 'profile') {
            $this->target->whereAdd(sprintf(
                'MATCH (%2$s.nickname, %2$s.fullname, %2$s.location, %2$s.bio, %2$s.homepage) ' .
                'AGAINST (\'%1$s\' IN BOOLEAN MODE)',
                $this->target->escape($q, true),
                $this->table
            ));
            if (strtolower($q) != $q) {
                $this->target->whereAdd(
                    sprintf(
                        'MATCH (%2$s.nickname, %2$s.fullname, %2$s.location, %2$s.bio, %2$s.homepage) ' .
                        'AGAINST (\'%1$s\' IN BOOLEAN MODE)',
                        $this->target->escape(strtolower($q), true),
                        $this->table
                    ),
                    'OR'
                );
            }
            return true;
        } elseif ($this->table === 'notice') {
            // Don't show direct messages.
            $this->target->whereAdd('notice.scope <> ' . Notice::MESSAGE_SCOPE);
            // Don't show imported notices
            $this->target->whereAdd('notice.is_local <> ' . Notice::GATEWAY);

            $this->target->whereAdd(sprintf(
                'MATCH (%2$s.content) AGAINST (\'%1$s\' IN BOOLEAN MODE)',
                $this->target->escape($q, true),
                $this->table
            ));
            if (strtolower($q) != $q) {
                $this->target->whereAdd(
                    sprintf(
                        'MATCH (%2$s.content) AGAINST (\'%1$s\' IN BOOLEAN MODE)',
                        $this->target->escape(strtolower($q), true),
                        $this->table
                    ),
                    'OR'
                );
            }

            return true;
        } else {
            throw new ServerException('Unknown table: ' . $this->table);
        }
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
