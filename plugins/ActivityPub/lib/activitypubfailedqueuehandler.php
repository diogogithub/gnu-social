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

/**
 * ActivityPub queue handler for notice distribution
 *
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivityPubFailedQueueHandler extends QueueHandler
{
    /**
     * Getter of the queue transport name.
     *
     * @return string transport name
     */
    public function transport(): string
    {
        return 'activitypub_failed';
    }

    /**
     * Notice distribution handler.
     *
     * @param array $to_failed [string to, Notice].
     * @return bool true on success, false otherwise
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function handle($to_failed): bool
    {
        [$other, $notice] = $to_failed;
        if (!($notice instanceof Notice)) {
            common_log(LOG_ERR, 'Got a bogus notice, not distributing');
            return true;
        }

        $profile = $notice->getProfile();

        if (!$profile->isLocal()) {
            return true;
        }

        if ($notice->source == 'activity') {
            common_log(LOG_ERR, "Ignoring distribution of notice:{$notice->id}: activity source");
            return true;
        }

        try {
            // Handling a Create?
            if (ActivityUtils::compareVerbs($notice->verb, [ActivityVerb::POST, ActivityVerb::SHARE])) {
                return $this->handle_create($profile, $notice, $other);
            }

            // Handling a Like?
            if (ActivityUtils::compareVerbs($notice->verb, [ActivityVerb::FAVORITE])) {
                return $this->onEndFavorNotice($profile, $notice, $other);
            }

            // Handling a Delete Note?
            if (ActivityUtils::compareVerbs($notice->verb, [ActivityVerb::DELETE])) {
                return $this->onStartDeleteOwnNotice($profile, $notice, $other);
            }
        } catch (Exception $e) {
            // Postman already re-enqueues for us
            common_debug('ActivityPub Failed Queue Handler:'.$e->getMessage());
        }

        return true;
    }

    private function handle_create($profile, $notice, $other)
    {
        // Handling an Announce?
        if ($notice->isRepeat()) {
            $repeated_notice = Notice::getKV('id', $notice->repeat_of);
            if ($repeated_notice instanceof Notice) {
                // That was it
                $postman = new Activitypub_postman($profile, $other);
                $postman->announce($notice, $repeated_notice);
            }

            // either made the announce or found nothing to repeat
            return true;
        }

        // That was it
        $postman = new Activitypub_postman($profile, $other);
        $postman->create_note($notice);
        return true;
    }

    /**
     * Notify remote users when their notices get favourited.
     *
     * @param Profile $profile of local user doing the faving
     * @param Notice $notice_liked Notice being favored
     * @return bool return value
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onEndFavorNotice(Profile $profile, Notice $notice, $other)
    {
        $postman = new Activitypub_postman($profile, $other);
        $postman->like($notice);

        return true;
    }

    /**
     * Notify remote users when their notices get deleted
     *
     * @param $user
     * @param $notice
     * @return bool hook flag
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onStartDeleteOwnNotice($profile, $notice, $other)
    {
        // Handle delete locally either because:
        // 1. There's no undo-share logic yet
        // 2. The deleting user has privileges to do so (locally)
        if ($notice->isRepeat() || ($notice->getProfile()->getID() != $profile->getID())) {
            return true;
        }

        $postman = new Activitypub_postman($profile, $other);
        $postman->delete_note($notice);
        return true;
    }
}
