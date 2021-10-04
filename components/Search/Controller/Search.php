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

namespace Component\Search\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Event;
use Component\Search\Util\Parser;
use Symfony\Component\HttpFoundation\Request;

class Search extends Controller
{
    public function handle(Request $request)
    {
        $q        = $this->string('q');
        $criteria = Parser::parse($q);

        $qb = DB::createQueryBuilder();
        $qb->select('note')->from('App\Entity\Note', 'note');
        Event::handle('SeachQueryAddJoins', [&$qb]);
        $qb->addCriteria($criteria);
        $query   = $qb->getQuery();
        $results = $query->execute();

        return [
            '_template' => 'search/show.html.twig',
            'results'   => $results,
        ];
    }
}
