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

namespace Plugin\StemWord;

use App\Core\Event;
use App\Core\Modules\Plugin;
use Wamania\Snowball\NotFoundException;
use Wamania\Snowball\StemmerFactory;

class StemWord extends Plugin
{
    public function onStemWord(string $language, string $word, ?string &$out)
    {
        $language = explode('_', $language)[0];
        try {
            $out = StemmerFactory::create($language)->stem($word);
        } catch (NotFoundException) {
            return Event::next;
        }
        return Event::stop;
    }
}
