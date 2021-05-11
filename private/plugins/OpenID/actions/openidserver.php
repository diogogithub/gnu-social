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
 * Settings for OpenID
 *
 * @category  Settings
 * @package   GNUsocial
 * @author    Craig Andrews <candrews@integralblue.com>
 * @copyright 2008-2009 StatusNet, Inc.
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once INSTALLDIR . '/plugins/OpenID/openid.php';

/**
 * Settings for OpenID
 *
 * Lets users add, edit and delete OpenIDs from their account
 *
 * @category  Settings
 * @package   GNUsocial
 * @author    Craig Andrews <candrews@integralblue.com>
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OpenidserverAction extends Action
{
    public $oserver;

    public function prepare(array $args = [])
    {
        parent::prepare($args);
        $this->oserver = oid_server();
        return true;
    }

    public function handle()
    {
        parent::handle();
        $request = $this->oserver->decodeRequest();
        if (in_array($request->mode, array('checkid_immediate',
            'checkid_setup'))) {
            if (!$this->scoped instanceof Profile) {
                if ($request->immediate) {
                    //cannot prompt the user to login in immediate mode, so answer false
                    $response = $this->generateDenyResponse($request);
                } else {
                    // Go log in, and then come back.
                    //
                    // Note: 303 redirect rather than 307 to avoid
                    // prompting user for form resubmission if we
                    // were POSTed here.
                    common_set_returnto($_SERVER['REQUEST_URI']);
                    common_redirect(common_local_url('login'), 303);
                }
            } elseif (
                in_array($request->identity, $this->scoped->getAliases())
                || $request->idSelect()
            ) {
                $user_openid_trustroot = User_openid_trustroot::pkeyGet([
                    'user_id'   => $this->scoped->getID(),
                    'trustroot' => $request->trust_root,
                ]);
                if (empty($user_openid_trustroot)) {
                    if ($request->immediate) {
                        //cannot prompt the user to trust this trust root in immediate mode, so answer false
                        $response = $this->generateDenyResponse($request);
                    } else {
                        common_ensure_session();
                        $_SESSION['openid_trust_root'] = $request->trust_root;
                        $allowResponse = $this->generateAllowResponse($request, $this->scoped);
                        $this->oserver->encodeResponse($allowResponse); //sign the response
                        $denyResponse = $this->generateDenyResponse($request);
                        $this->oserver->encodeResponse($denyResponse); //sign the response
                        $_SESSION['openid_allow_url'] = $allowResponse->encodeToUrl();
                        $_SESSION['openid_deny_url'] = $denyResponse->encodeToUrl();

                        // Ask the user to trust this trust root...
                        //
                        // Note: 303 redirect rather than 307 to avoid
                        // prompting user for form resubmission if we
                        // were POSTed here.
                        common_redirect(common_local_url('openidtrust'), 303);
                    }
                } else {
                    //user has previously authorized this trust root
                    $response = $this->generateAllowResponse($request, $this->scoped);
                }
            } elseif ($request->immediate) {
                $response = $this->generateDenyResponse($request);
            } else {
                //invalid
                $this->clientError(sprintf(
                    // TRANS: OpenID plugin client error given trying to add an unauthorised OpenID to a user (403).
                    // TRANS: %s is a request identity.
                    _m('You are not authorized to use the identity %s.'),
                    $request->identity
                ), 403);
            }
        } else {
            $response = $this->oserver->handleRequest($request);
        }

        if ($response) {
            $response = $this->oserver->encodeResponse($response);
            if ($response->code != AUTH_OPENID_HTTP_OK) {
                http_response_code($response->code);
            }

            if ($response->headers) {
                foreach ($response->headers as $k => $v) {
                    header("$k: $v");
                }
            }
            $this->raw($response->body);
        } else {
            $this->clientError(
                // TRANS: OpenID plugin client error given when not getting a response for a given OpenID provider (500).
                _m('Just an OpenID provider. Nothing to see here, move along...'),
                500
            );
        }
    }

    public function generateAllowResponse($request, Profile $profile)
    {
        $response = $request->answer(true, null, $profile->getUrl());
        $user = $profile->getUser();

        $sreg_data = [
            'fullname' => $profile->getFullname(),
            'nickname' => $profile->getNickname(),
            'email' => $user->email,    // FIXME: Should we make the email optional?
            'language' => $user->language,
            'timezone' => $user->timezone,
        ];
        $sreg_request = Auth_OpenID_SRegRequest::fromOpenIDRequest($request);
        $sreg_response = Auth_OpenID_SRegResponse::extractResponse(
            $sreg_request,
            $sreg_data
        );
        $sreg_response->toMessage($response->fields);
        return $response;
    }

    public function generateDenyResponse($request)
    {
        $response = $request->answer(false);
        return $response;
    }
}
