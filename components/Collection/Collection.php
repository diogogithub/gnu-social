<?php

declare(strict_types = 1);

namespace Component\Collection;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Component;
use App\Entity\Actor;
use App\Util\Formatting;
use Component\Collection\Util\Parser;
use Component\Subscription\Entity\ActorSubscription;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class Collection extends Component
{
    /**
     * Perform a high level query on notes or actors
     *
     * Supports a variety of query terms and is used both in feeds and
     * in search. Uses query builders to allow for extension
     */
    public static function query(string $query, int $page, ?string $locale = null, ?Actor $actor = null): array
    {
        $note_criteria  = null;
        $actor_criteria = null;
        if (!empty($query = trim($query))) {
            [$note_criteria, $actor_criteria] = Parser::parse($query, $locale, $actor);
        }
        $note_qb  = DB::createQueryBuilder();
        $actor_qb = DB::createQueryBuilder();
        // TODO consider selecting note related stuff, to avoid separate queries (though they're cached, so maybe it's okay)
        $note_qb->select('note')->from('App\Entity\Note', 'note')->orderBy('note.created', 'DESC')->addOrderBy('note.id', 'DESC');
        $actor_qb->select('actor')->from('App\Entity\Actor', 'actor')->orderBy('actor.created', 'DESC')->addOrderBy('actor.id', 'DESC');
        Event::handle('CollectionQueryAddJoins', [&$note_qb, &$actor_qb, $note_criteria, $actor_criteria]);

        $notes  = [];
        $actors = [];
        if (!\is_null($note_criteria)) {
            $note_qb->addCriteria($note_criteria);
            $notes = $note_qb->getQuery()->execute();
        }

        if (!\is_null($actor_criteria)) {
            $actor_qb->addCriteria($actor_criteria);
            $actors = $actor_qb->getQuery()->execute();
        }

        // N.B.: Scope is only enforced at FeedController level
        return ['notes' => $notes ?? null, 'actors' => $actors ?? null];
    }

    public function onCollectionQueryAddJoins(QueryBuilder &$note_qb, QueryBuilder &$actor_qb): bool
    {
        $note_qb->leftJoin(ActorSubscription::class, 'subscription', Expr\Join::WITH, 'note.actor_id = subscription.subscribed_id')
            ->leftJoin(Actor::class, 'note_actor', Expr\Join::WITH, 'note.actor_id = note_actor.id');
        return Event::next;
    }

    /**
     * Convert $term to $note_expr and $actor_expr, search criteria. Handles searching for text
     * notes, for different types of actors and for the content of text notes
     */
    public function onCollectionQueryCreateExpression(ExpressionBuilder $eb, string $term, ?string $locale, ?Actor $actor, &$note_expr, &$actor_expr)
    {
        if (str_contains($term, ':')) {
            $term = explode(':', $term);
            if (Formatting::startsWith($term[0], 'note')) {
                switch ($term[0]) {
                case 'notes-all':
                    $note_expr = $eb->neq('note.created', null);
                    break;
                case 'note-local':
                    $note_expr = $eb->eq('note.is_local', filter_var($term[1], \FILTER_VALIDATE_BOOLEAN));
                    break;
                case 'note-types':
                case 'notes-include':
                case 'note-filter':
                    if (\is_null($note_expr)) {
                        $note_expr = [];
                    }
                    if (array_intersect(explode(',', $term[1]), ['text', 'words']) !== []) {
                        $note_expr[] = $eb->neq('note.content', null);
                    } else {
                        $note_expr[] = $eb->eq('note.content', null);
                    }
                    break;
                case 'note-conversation':
                    $note_expr = $eb->eq('note.conversation_id', (int) trim($term[1]));
                    break;
                case 'note-from':
                case 'notes-from':
                    $subscribed_expr = $eb->eq('subscription.subscriber_id', $actor->getId());
                    $type_consts     = [];
                    if ($term[1] === 'subscribed') {
                        $type_consts = null;
                    }
                    foreach (explode(',', $term[1]) as $from) {
                        if (str_starts_with($from, 'subscribed-')) {
                            [, $type] = explode('-', $from);
                            if (\in_array($type, ['actor', 'actors'])) {
                                $type_consts = null;
                            } else {
                                $type_consts[] = \constant(Actor::class . '::' . mb_strtoupper($type));
                            }
                        }
                    }
                    if (\is_null($type_consts)) {
                        $note_expr = $subscribed_expr;
                    } elseif (!empty($type_consts)) {
                        $note_expr = $eb->andX($subscribed_expr, $eb->in('note_actor.type', $type_consts));
                    }
                    break;
                }
            } elseif (Formatting::startsWith($term, 'actor-')) {
                switch ($term[0]) {
                    case 'actor-types':
                    case 'actors-include':
                    case 'actor-filter':
                    case 'actor-local':
                        if (\is_null($actor_expr)) {
                            $actor_expr = [];
                        }
                        foreach (
                            [
                                Actor::PERSON => ['person', 'people'],
                                Actor::GROUP => ['group', 'groups'],
                                Actor::ORGANISATION => ['org', 'orgs', 'organization', 'organizations', 'organisation', 'organisations'],
                                Actor::BOT => ['bot', 'bots'],
                            ] as $type => $match) {
                            if (array_intersect(explode(',', $term[1]), $match) !== []) {
                                $actor_expr[] = $eb->eq('actor.type', $type);
                            } else {
                                $actor_expr[] = $eb->neq('actor.type', $type);
                            }
                        }
                        break;
                }
            }
        } else {
            $note_expr = $eb->contains('note.content', $term);
        }
        return Event::next;
    }
}
