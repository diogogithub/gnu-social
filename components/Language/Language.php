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

namespace Component\Language;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Component;
use App\Entity\Actor;
use App\Entity\Note;
use Functional as F;

class Language extends Component
{
    public function onFilterNoteList(Actor $actor, array &$notes)
    {
        $language        = $actor->getTopLanguage();
        $locale          = explode('_', $language->getLocale())[0];
        $language_family = F\reindex(
            DB::dql('select l from language l where l.locale like :locale', ['locale' => $locale . '%']),
            fn ($l) => $l->getId(),
        );

        $notes = F\select(
            $notes,
            fn (Note $n) => \array_key_exists($n->getLanguageId(), $language_family) && !str_contains($language_family[$n->getLanguageId()]->getLocale(), '_') ? \in_array($n->getLanguageId(), array_keys($language_family)) : $n->getLanguageId() === $language->getId(),
        );

        return Event::next;
    }
}
