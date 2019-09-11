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

class SensitiveContentSettingsAction extends SettingsAction
{
    public function title()
    {
        return _m('Sensitive content settings');
    }

    public function getInstructions()
    {
        return _m('Set preferences for display of "sensitive" content');
    }

    public function showContent()
    {
        $user = $this->scoped->getUser();

        $this->elementStart(
            'form',
            [
                'method' => 'post',
                'id'     => 'sensitivecontent',
                'class'  => 'form_settings',
                'action' => common_local_url('sensitivecontentsettings'),
            ]
        );

        $this->elementStart('fieldset');
        $this->hidden('token', common_session_token());
        $this->elementStart('ul', 'form_data');

        $this->elementStart('li');
        $this->checkbox(
            'hidesensitive',
            _('Hide attachments in posts hashtagged #NSFW'),
            ($this->arg('hidesensitive') ?
            $this->boolean('hidesensitive') : $this->scoped->getPref('MoonMan', 'hide_sensitive', 0))
        );
        $this->elementEnd('li');


        $this->elementEnd('ul');
        $this->submit('save', _m('BUTTON', 'Save'));

        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    public function doPost()
    {
        $hidesensitive = $this->boolean('hidesensitive') ? '1' : '0';
        $this->scoped->setPref('MoonMan', 'hide_sensitive', $hidesensitive);
        return _('Settings saved.');
    }
}
