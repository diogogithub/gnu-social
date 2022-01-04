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

namespace Component\Circle\Controller;

use function App\Core\I18n\_m;
use App\Entity\LocalUser;
use App\Util\Exception\ClientException;
use Component\Circle\Entity\ActorCircle;
use Component\Collection\Util\Controller\CircleController;

class Circle extends CircleController
{
    public function circleById(int|ActorCircle $circle_id): array
    {
        $circle = \is_int($circle_id) ? ActorCircle::getByPK(['id' => $circle_id]) : $circle_id;
        unset($circle_id);
        if (\is_null($circle)) {
            throw new ClientException(_m('No such circle.'), 404);
        } else {
            return [
                '_template'        => 'collection/actors.html.twig',
                'title'            => _m('Circle'),
                'empty_message'    => _m('No members.'),
                'sort_form_fields' => [],
                'page'             => $this->int('page') ?? 1,
                'actors'           => $circle->getTaggedActors(),
            ];
        }
    }

    public function circleByTaggerIdAndTag(int $tagger_id, string $tag): array
    {
        return $this->circleById(ActorCircle::getByPK(['tagger' => $tagger_id, 'tag' => $tag]));
    }

    public function circleByTaggerNicknameAndTag(string $tagger_nickname, string $tag): array
    {
        return $this->circleById(ActorCircle::getByPK(['tagger' => LocalUser::getByNickname($tagger_nickname)->getId(), 'tag' => $tag]));
    }
}
