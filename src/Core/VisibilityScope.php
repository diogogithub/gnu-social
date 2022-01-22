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

namespace App\Core;

// The domain of this enum are Objects
enum VisibilityScope: int // having an int is just convenient
{
    case EVERYWHERE = 1;  // Can be shown everywhere (default)
    case LOCAL      = 2;  // Non-public and non-federated (default in private sites)
    case ADDRESSEE  = 4;  // Only if the actor is the author or one of the targets
    case GROUP      = 8;  // Only in the Group feed
    case COLLECTION = 16; // Only for the collection to see (same as addressee but not available in feeds, notifications only)
    case MESSAGE    = 32; // Direct Message (same as Collection, but also with dedicated plugin)
};
