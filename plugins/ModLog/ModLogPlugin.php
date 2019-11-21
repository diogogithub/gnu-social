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
 * @category  Moderation
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2012 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Moderation logging
 *
 * Shows a history of moderation for this user in the sidebar
 *
 * @copyright 2012 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ModLogPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';
    const VIEWMODLOG = 'ModLogPlugin::VIEWMODLOG';

    /**
     * Database schema setup
     *
     * We keep a moderation log table
     *
     * @see Schema
     * @see ColumnDef
     *
     * @return boolean hook value; true means continue processing, false means stop.
     */

    public function onCheckSchema()
    {
        $schema = Schema::get();

        $schema->ensureTable('mod_log', ModLog::schemaDef());

        return true;
    }

    public function onEndGrantRole($profile, $role)
    {
        $modlog = new ModLog();

        $modlog->id         = UUID::gen();
        $modlog->profile_id = $profile->id;

        $cur = common_current_user();
        
        if (!empty($cur)) {
            $modlog->moderator_id = $cur->id;
        }

        $modlog->role     = $role;
        $modlog->is_grant = true;
        $modlog->created  = common_sql_now();

        $modlog->insert();

        return true;
    }

    public function onEndRevokeRole($profile, $role)
    {
        $modlog = new ModLog();

        $modlog->id = UUID::gen();

        $modlog->profile_id = $profile->id;

        $scoped = Profile::current();
        
        if ($scoped instanceof Profile) {
            $modlog->moderator_id = $scoped->getID();
        }

        $modlog->role     = $role;
        $modlog->is_grant = false;
        $modlog->created  = common_sql_now();

        $modlog->insert();

        return true;
    }

    public function onEndShowSections(Action $action)
    {
        if (!$action instanceof ShowstreamAction) {
            // early return for actions we're not interested in
            return true;
        }

        $scoped = $action->getScoped();
        if (!$scoped instanceof Profile || !$scoped->hasRight(self::VIEWMODLOG)) {
            // only continue if we are allowed to VIEWMODLOG
            return true;
        }

        $profile = $action->getTarget();

        $ml = new ModLog();

        $ml->profile_id = $profile->getID();
        $ml->orderBy("created");

        $cnt = $ml->find();

        if ($cnt > 0) {
            $action->elementStart('div', array('id' => 'entity_mod_log',
                                               'class' => 'section'));

            $action->element('h2', null, _('Moderation'));

            $action->elementStart('table');

            while ($ml->fetch()) {
                $action->elementStart('tr');
                $action->element('td', null, strftime('%y-%m-%d', strtotime($ml->created)));
                $action->element('td', null, sprintf(($ml->is_grant) ? _('+%s') : _('-%s'), $ml->role));
                $action->elementStart('td');
                if ($ml->moderator_id) {
                    $mod = Profile::getByID($ml->moderator_id);
                    if (empty($mod)) {
                        $action->text(_('[unknown]'));
                    } else {
                        $action->element(
                            'a',
                            [
                                'href' => $mod->getUrl(),
                                'title' => $mod->getFullname(),
                            ],
                            $mod->getNickname()
                        );
                    }
                } else {
                    $action->text(_('[unknown]'));
                }
                $action->elementEnd('td');
                $action->elementEnd('tr');
            }

            $action->elementEnd('table');

            $action->elementEnd('div');
        }
    }

    public function onUserRightsCheck($profile, $right, &$result)
    {
        switch ($right) {
        case self::VIEWMODLOG:
            $result = ($profile->hasRole(Profile_role::MODERATOR) || $profile->hasRole('modhelper'));
            return false;
        default:
            return true;
        }
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'ModLog',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Evan Prodromou',
                            'homepage' => GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/ModLog',
                            'description' =>
                            _m('Show the moderation history for a profile in the sidebar'));
        return true;
    }
}
