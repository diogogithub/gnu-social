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
 *
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019-2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
defined('GNUSOCIAL') || die();

/**
 * @copyright 2019-2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class activitypubqueuehandler extends QueueHandler
{
    /**
     * Getter of the queue transport name.
     *
     * @return string transport name
     */
    public function transport(): string
    {
        return 'activitypub';
    }

    /**
     * Notice distribution handler.
     *
     * @param Notice $notice notice to be distributed.
     *
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     * @throws ServerException
     *
     * @return bool true on success, false otherwise
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function handle($notice): bool
    {
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

        $other = Activitypub_profile::from_profile_collection(
            $notice->getAttentionProfiles()
        );

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
            // Postman handles issues with the failed queue
            common_debug('ActivityPub Queue Handler:'.$e->getMessage());
        }

        return true;
    }

    /**
     * Handle notice creation and propagation
     *
     * @param mixed $profile
     * @param mixed $notice
     * @param mixed $other
     */
    private function handle_create($profile, $notice, $other)
    {
        // Handling a reply?
        if ($notice->reply_to) {
            try {
                $parent_notice = $notice->getParent();

                try {
                    $other[] = Activitypub_profile::from_profile($parent_notice->getProfile());
                } catch (Exception $e) {
                    // Local user can be ignored
                }

                foreach ($parent_notice->getAttentionProfiles() as $mention) {
                    try {
                        $other[] = Activitypub_profile::from_profile($mention);
                    } catch (Exception $e) {
                        // Local user can be ignored
                    }
                }
            } catch (NoParentNoticeException $e) {
                // This is not a reply to something (has no parent)
            } catch (NoResultException $e) {
                // Parent author's profile not found! Complain louder?
                common_log(
                    LOG_ERR,
                    "Parent notice's author not found: " . $e->getMessage()
                );
            }
        }

        // Handling an Announce?
        if ($notice->isRepeat()) {
            $repeated_notice = Notice::getKV('id', $notice->repeat_of);
            if ($repeated_notice instanceof Notice) {
                $other = array_merge(
                    $other,
                    Activitypub_profile::from_profile_collection(
                        $repeated_notice->getAttentionProfiles()
                    )
                );

                try {
                    $other[] = Activitypub_profile::from_profile(
                        $repeated_notice->getProfile()
                    );
                } catch (Exception $e) {
                    // Local user can be ignored
                }

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
     * @param Notice  $notice  Notice being favored
     * @param mixed   $other
     *
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     *
     * @return bool return value
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onEndFavorNotice(Profile $profile, Notice $notice, $other)
    {
        $notice_liked = $notice->getParent();
        if ($notice_liked->reply_to) {
            try {
                $parent_notice = $notice_liked->getParent();

                try {
                    $other[] = Activitypub_profile::from_profile($parent_notice->getProfile());
                } catch (Exception $e) {
                    // Local user can be ignored
                }

                $other = array_merge(
                    $other,
                    Activitypub_profile::from_profile_collection(
                        $parent_notice->getAttentionProfiles()
                    )
                );
            } catch (NoParentNoticeException $e) {
                // This is not a reply to something (has no parent)
            } catch (NoResultException $e) {
                // Parent author's profile not found! Complain louder?
                common_log(LOG_ERR, "Parent notice's author not found: " . $e->getMessage());
            }
        }

        $postman = new Activitypub_postman($profile, $other);
        $postman->like($notice);

        return true;
    }

    /**
     * Notify remote users when their notices get de-favourited.
     *
     * @param Profile $profile of local user doing the de-faving
     * @param Notice  $notice  Notice being favored
     * @param mixed   $other
     *
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     *
     * @return bool return value
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onEndDisfavorNotice(Profile $profile, Notice $notice, $other)
    {
        if ($notice->reply_to) {
            try {
                $parent_notice = $notice->getParent();

                try {
                    $other[] = Activitypub_profile::from_profile($parent_notice->getProfile());
                } catch (Exception $e) {
                    // Local user can be ignored
                }

                $other = array_merge(
                    $other,
                    Activitypub_profile::from_profile_collection(
                        $parent_notice->getAttentionProfiles()
                    )
                );
            } catch (NoParentNoticeException $e) {
                // This is not a reply to something (has no parent)
            } catch (NoResultException $e) {
                // Parent author's profile not found! Complain louder?
                common_log(LOG_ERR, "Parent notice's author not found: " . $e->getMessage());
            }
        }

        $postman = new Activitypub_postman($profile, $other);
        $postman->undo_like($notice);

        return true;
    }

    /**
     * Notify remote users when their notices get deleted
     *
     * @param $user
     * @param $notice
     * @param mixed $profile
     * @param mixed $other
     *
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     *
     * @return bool hook flag
     *
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

        if ($notice->reply_to) {
            try {
                $parent_notice = $notice->getParent();

                try {
                    $other[] = Activitypub_profile::from_profile($parent_notice->getProfile());
                } catch (Exception $e) {
                    // Local user can be ignored
                }

                $other = array_merge(
                    $other,
                    Activitypub_profile::from_profile_collection(
                        $parent_notice->getAttentionProfiles()
                    )
                );
            } catch (NoParentNoticeException $e) {
                // This is not a reply to something (has no parent)
            } catch (NoResultException $e) {
                // Parent author's profile not found! Complain louder?
                common_log(LOG_ERR, "Parent notice's author not found: " . $e->getMessage());
            }
        }

        $postman = new Activitypub_postman($profile, $other);
        $postman->delete_note($notice);
        return true;
    }
}
