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
 * Plugin to enable Single Sign On via CAS (Central Authentication Service)
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Craig Andrews <candrews@integralblue.com>
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class CasAuthenticationPlugin extends AuthenticationPlugin
{
    const PLUGIN_VERSION = '2.0.0';

    public $server;
    public $port = 443;
    public $path = '';
    public $takeOverLogin = false;
    public $user_whitelist = null;

    public function checkPassword($username, $password)
    {
        global $casTempPassword;
        return ($casTempPassword == $password);
    }

    public function onArgsInitialize(&$args)
    {
        if ($this->takeOverLogin && $args['action'] === 'login') {
            $args['action'] = 'caslogin';
        }
    }

    public function onStartInitializeRouter($m)
    {
        $m->connect('main/cas', array('action' => 'caslogin'));
        return true;
    }

    public function onEndLoginGroupNav($action)
    {
        $action_name = $action->trimmed('action');

        $action->menuItem(
            common_local_url('caslogin'),
            // TRANS: Menu item. CAS is Central Authentication Service.
            _m('CAS'),
            // TRANS: Tooltip for menu item. CAS is Central Authentication Service.
            _m('Login or register with CAS.'),
            ($action_name === 'caslogin')
        );

        return true;
    }

    public function onEndShowPageNotice($action)
    {
        $name = $action->trimmed('action');

        switch ($name) {
            case 'login':
                // TRANS: Invitation to users with a CAS account to log in using the service.
                // TRANS: "[CAS login]" is a link description. (%%action.caslogin%%) is the URL.
                // TRANS: These two elements may not be separated.
                $instr = _m('(Have an account with CAS? ' .
                    'Try our [CAS login](%%action.caslogin%%)!)');
                break;
            default:
                return true;
        }

        $output = common_markup_to_html($instr);
        $action->raw($output);
        return true;
    }

    public function onLoginAction($action, &$login)
    {
        switch ($action) {
            case 'caslogin':
                $login = true;
                return false;
            default:
                return true;
        }
    }

    public function onInitializePlugin()
    {
        parent::onInitializePlugin();
        if (!isset($this->server)) {
            // TRANS: Exception thrown when the CAS Authentication plugin has been configured incorrectly.
            throw new Exception(_m("Specifying a server is required."));
        }
        if (!isset($this->port)) {
            // TRANS: Exception thrown when the CAS Authentication plugin has been configured incorrectly.
            throw new Exception(_m("Specifying a port is required."));
        }
        if (!isset($this->path)) {
            // TRANS: Exception thrown when the CAS Authentication plugin has been configured incorrectly.
            throw new Exception(_m("Specifying a path is required."));
        }
        //These values need to be accessible to a action object
        //I can't think of any other way than global variables
        //to allow the action instance to be able to see values :-(
        global $casSettings;
        $casSettings = array();
        $casSettings['server']=$this->server;
        $casSettings['port']=$this->port;
        $casSettings['path']=$this->path;
        $casSettings['takeOverLogin']=$this->takeOverLogin;
        $casSettings['user_whitelist']=$this->user_whitelist;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'CAS Authentication',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Craig Andrews',
                            'homepage' => 'https://git.gnu.io/gnu/gnu-social/tree/master/plugins/CasAuthentication',
                            // TRANS: Plugin description. CAS is Central Authentication Service.
                            'rawdescription' => _m('The CAS Authentication plugin allows for StatusNet to handle authentication through CAS (Central Authentication Service).'));
        return true;
    }
}
