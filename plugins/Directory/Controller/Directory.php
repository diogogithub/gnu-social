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

namespace Plugin\Directory\Controller;

use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Entity\Actor;
use App\Util\Exception\BugFoundException;
use App\Util\Exception\ClientException;
use Component\Feed\Util\FeedController;
use Symfony\Component\HttpFoundation\Request;

class Directory extends FeedController
{
    public const PER_PAGE       = 32;
    public const ALLOWED_FIELDS = ['nickname', 'created', 'modified', 'activity', 'subscribers'];

    /**
     * Function responsible for displaying a list of actors of a given
     * $actor_type, sorted by the `order_by` GET parameter, if given
     */
    private function impl(Request $request, string $template, int $actor_type): array
    {
        if ($actor_type !== Actor::PERSON && $actor_type !== Actor::GROUP) {
            throw new BugFoundException("Unimplemented for actor type: {$actor_type}");
        }

        $page   = $this->int('page') ?? 1;
        $limit  = self::PER_PAGE;
        $offset = self::PER_PAGE * ($page - 1);

        // -------- Figure out the order by field and operator --------
        $order_by_qs = $this->string('order_by');
        if (!\is_null($order_by_qs) && mb_detect_encoding($order_by_qs, 'ASCII', strict: true) !== false) {
            $order_by_op = mb_substr($order_by_qs, -1);
            if (\in_array($order_by_op, ['^', '<'])) {
                $order_by_field = mb_substr($order_by_qs, 0, -1);
                $order_by_op    = 'DESC';
            } elseif (\in_array($order_by_op, ['v', '>'])) {
                $order_by_field = mb_substr($order_by_qs, 0, -1);
                $order_by_op    = 'ASC';
            } else {
                $order_by_field = $order_by_qs;
                $order_by_op    = 'ASC';
            }

            if (!\in_array($order_by_field, self::ALLOWED_FIELDS)) {
                throw new ClientException(_m('Invalid order by given: {order_by}', ['{order_by}' => $order_by_field]));
            }
        } else {
            $order_by_field = 'nickname';
            $order_by_op    = 'ASC';
        }
        $order_by = [$order_by_field => $order_by_op];
        // -------- *** --------

        // -------- Query builder for selecting actors joined with another table, namely activity and group_inbox --------
        $general_query_fn_fn = function (string $func, string $order) use ($limit, $offset) {
            return fn (string $table, string $join_field) => fn (int $actor_type) => DB::sql(
                <<<EOQ
                    select {select}
                    from actor actr
                    join (
                        select tbl.{$join_field}, {$func}(tbl.created) as created
                        from {$table} tbl
                        group by tbl.{$join_field}
                    ) actor_activity on actr.id = actor_activity.{$join_field}
                    where actr.type = :type
                    order by actor_activity.{$order}
                    limit :limit offset :offset
                    EOQ,
                [
                    'type'   => $actor_type,
                    'limit'  => $limit,
                    'offset' => $offset,
                ],
                ['actr' => Actor::class],
            );
        };
        // -------- *** --------

        // -------- Start setting up the queries --------
        $actor_query_fn    = fn (int $actor_type) => DB::findBy(Actor::class, ['type' => $actor_type], order_by: $order_by, limit: $limit, offset: $offset);
        $modified_query_fn = $general_query_fn_fn(func: $order_by_op === 'ASC' ? 'MAX' : 'MIN', order: "created {$order_by_op}");
        $activity_query_fn = $general_query_fn_fn(func: 'COUNT', order: "created {$order_by_op}");
        // -------- *** --------

        // -------- Figure out the final query --------
        $query_fn = match ($order_by_field) {
            'nickname', 'created' => $actor_query_fn, // select only from actors

            'modified'        => match ($actor_type) { // select by most/least recent activity
                Actor::PERSON => $modified_query_fn(table: 'activity', join_field: 'actor_id'),
                Actor::GROUP  => $modified_query_fn(table: 'group_inbox', join_field: 'group_id'),
            },

            'activity'        => match ($actor_type) { // select by most/least activity amount
                Actor::PERSON => $activity_query_fn(table: 'activity', join_field: 'actor_id'),
                Actor::GROUP  => $activity_query_fn(table: 'group_inbox', join_field: 'group_id'),
            },

            default => throw new BugFoundException("Unkown order by found, but should have been validated: {$order_by_field}"),
        };
        // -------- *** --------

        return [
            '_template' => $template,
            'actors'    => $query_fn($actor_type),
            'page'      => $page,
        ];
    }

    public function people(Request $request): array
    {
        return $this->impl($request, 'directory/people.html.twig', Actor::PERSON);
    }

    public function groups(Request $request): array
    {
        return $this->impl($request, 'directory/groups.html.twig', Actor::GROUP);
    }
}
