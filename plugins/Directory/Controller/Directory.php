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
use App\Entity\Actor;
use App\Util\Exception\BugFoundException;
use App\Util\Exception\ClientException;
use Component\Feed\Util\FeedController;
use function App\Core\I18n\_m;
use Symfony\Component\HttpFoundation\Request;

class Directory extends FeedController
{

    const PER_PAGE = 32;
    const ALLOWED_FIELDS = ['nickname', 'created', 'modified', 'activity', 'subscribers'];

    private function impl(Request $request, string $template, int $actor_type): array
    {
        $page = $this->int('page') ?? 1;
        $limit = self::PER_PAGE;
        $offset = self::PER_PAGE * ($page - 1);

        $order_by_qs = $this->string('order_by');
        if (!\is_null($order_by_qs) && mb_detect_encoding($order_by_qs, 'ASCII', strict: true) !== false) {

            $order_by_op = substr($order_by_qs, -1);
            if (\in_array($order_by_op, ['^', '<'])) {
                $order_by_field = substr($order_by_qs, 0, -1);
                $order_by_op = 'ASC';
            } else if (\in_array($order_by_op, ['v', '>'])) {
                $order_by_field = substr($order_by_qs, 0, -1);
                $order_by_op = 'DESC';
            } else {
                $order_by_field = $order_by_qs;
                $order_by_op = 'ASC';
            }

            if (!\in_array($order_by_field, self::ALLOWED_FIELDS)) {
                throw new ClientException(_m('Invalid order by given: {order_by}', ['{order_by}' => $order_by_field]));
            }
        } else {
            $order_by_field = 'nickname';
            $order_by_op = 'ASC';
        }

        $order_by = [$order_by_field => $order_by_op];
        $route = $request->get('_route');

        $query_fn = function (int $actor_type, string $table, string $join_field) use ($limit, $offset) {
            return function (string $func, string $order) use ($actor_type, $table, $join_field, $limit, $offset) {
                return DB::sql(
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
                        'type' => $actor_type,
                        'limit' => $limit,
                        'offset' => $offset,
                    ],
                    ['actr' => Actor::class]
                );
            };
        };

        $person_activity_query = $query_fn(actor_type: Actor::PERSON, table: 'activity', join_field: 'actor_id');
        $group_activity_query  = $query_fn(actor_type: Actor::GROUP, table: 'group_inbox', join_field: 'group_id');

        switch ($order_by_field) {
        case 'nickname':
        case 'created':
            $actors = DB::findBy(Actor::class, ['type' => $actor_type], order_by: $order_by, limit: $limit, offset: $offset);
            break;

        case 'modified':
            $query = match ($actor_type) {
                Actor::PERSON => $person_activity_query,
                Actor::GROUP  => $group_activity_query,
                default => throw new BugFoundException("Unimplemented for actor type: {$actor_type}"),
            };
            $actors = $query(func: $order_by_op === 'ASC' ? 'MAX' : 'MIN', order: "created {$order_by_op}");
            break;

        case 'activity':
            $query = match ($actor_type) {
                Actor::PERSON => $person_activity_query,
                Actor::GROUP  => $group_activity_query,
                default => throw new BugFoundException("Unimplemented for actor type: {$actor_type}"),
            };
            $actors = $query(func: 'COUNT', order: "created {$order_by_op}");
            break;

        default:
            throw new BugFoundException("Unkown order by found, but should have been validated: {$order_by_field}");
        }

        return [
            '_template' => $template,
            'actors'    => $actors,
            'page'      => $page,
        ];
    }

    /**
     * people stream
     *
     * @return array template
     */
    public function people(Request $request): array
    {
        return $this->impl($request, 'directory/people.html.twig', Actor::PERSON);
    }

    /**
     * groups stream
     *
     * @return array template
     */
    public function groups(Request $request): array
    {
        return $this->impl($request, 'directory/groups.html.twig', Actor::GROUP);
    }
}
