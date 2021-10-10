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

namespace Plugin\ActivityPub\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Util\Exception\ClientException;
use Plugin\ActivityPub\ActivityPub;
use Plugin\ActivityPub\Util\Model\AS2ToEntity\AS2ToEntity;
use Plugin\ActivityPub\Util\Response\TypeResponse;
use Plugin\ActivityPub\Util\Type;
use Plugin\ActivityPub\Util\Type\Util;

class Inbox extends Controller
{
    /**
     * Inbox handler
     */
    public function handle(?int $gsactor_id = null)
    {
        if (!\is_null($gsactor_id)) {
            $user = DB::find('local_user', ['id' => $gsactor_id]);
            if (\is_null($user)) {
                throw new ClientException(_m('No such actor.'), 404);
            }
        }

        // Check accept header
        ActivityPub::validateAcceptHeader(
            $this->request->headers->get('accept'),
            true,
        );

        // TODO: Check if Actor can post

        // Get content
        $payload = Util::decodeJson(
            (string) $this->request->getContent(),
        );

        // Cast as an ActivityStreams type
        $type = Type::create($payload);

        // TODO: Check if Actor has authority over payload

        // Store Activity
        dd(AS2ToEntity::store(activity: $type->toArray(), source: 'ActivityPub'));
        DB::flush();

        return new TypeResponse($type, status: 202);
    }
}
