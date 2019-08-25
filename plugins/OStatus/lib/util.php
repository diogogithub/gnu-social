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

defined('GNUSOCIAL') || die();

/**
 * Pull (and store) remote profile from the its uri
 * 
 * @param string $uri
 * @return null|Ostatus_profile
 */
function pullRemoteProfile(string $uri): ?Ostatus_profile
{
    $validate = new Validate();

    try {
        $uri = Discovery::normalize($uri);
    } catch (Exception $e) {
        return null;
    }

    try {
        if (Discovery::isAcct($uri) && $validate->email(mb_substr($uri, 5))) {
            $profile = Ostatus_profile::ensureWebfinger($uri);
        } else if ($validate->uri($uri)) {
            $profile = Ostatus_profile::ensureProfileURL($uri);
        } else {
            common_log(LOG_ERR, 'Invalid address format.');
            return null;
        }
        return $profile;
    } catch (FeedSubBadURLException $e) {
        common_log(LOG_ERR, 'Invalid URL or could not reach server.');
    } catch (FeedSubBadResponseException $e) {
        common_log(LOG_ERR, 'Cannot read feed; server returned error.');
    } catch (FeedSubEmptyException $e) {
        common_log(LOG_ERR, 'Cannot read feed; server returned an empty page.');
    } catch (FeedSubBadHTMLException $e) {
        common_log(LOG_ERR, 'Bad HTML, could not find feed link.');
    } catch (FeedSubNoFeedException $e) {
        common_log(LOG_ERR, 'Could not find a feed linked from this URL.');
    } catch (FeedSubUnrecognizedTypeException $e) {
        common_log(LOG_ERR, 'Not a recognized feed type.');
    } catch (FeedSubNoHubException $e) {
        common_log(LOG_ERR, 'No hub found.');
    } catch (Exception $e) {
        common_log(LOG_ERR, sprintf('Bad feed URL: %s %s', get_class($e), $e->getMessage()));
    }

    return null;
}