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
 * Allows administrators to define additional profile fields for the users of a GNU social installation.
 *
 * @category  Widget
 * @package   GNU social
 * @author    Max Shinn <trombonechamp@gmail.com>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class ProfilefieldsAdminPanelAction extends AdminPanelAction
{
    /**
     * Title of the page
     *
     * @return string Title of the page
     */
    public function title(): string
    {
        return _m('Profile fields');
    }

    /**
     * Instructions for use
     *
     * @return string instructions for use
     */
    public function getInstructions(): string
    {
        return _m('GNU Social custom profile fields');
    }

    public function showForm(): void
    {
        $form = new ProfilefieldsAdminForm($this);
        $form->show();
    }

    protected function saveField(): void
    {
        $field = GNUsocialProfileExtensionField::getKV('id', $this->trimmed('id'));
        if (!$field) {
            $field = new GNUsocialProfileExtensionField();
            $field->systemname = $this->trimmed('systemname');
            if (!gnusocial_field_systemname_validate($field->systemname)) {
                $this->clientError(_m('Internal system name must be unique and consist of only alphanumeric characters!'));
            }
        }
        $field->title = $this->trimmed('title');
        $field->description = $this->trimmed('description');
        $field->type = $this->trimmed('type');
        if ($field->id) {
            if ($field->validate()) {
                $field->update();
            } else {
                $this->clientError(_m('There was an error with the field data.'));
            }
        } else {
            $field->insert();
        }
    }

    protected function removeField(): void
    {
        // Grab field
        $field = GNUsocialProfileExtensionField::getKV('id', $this->trimmed('id'));
        if (!$field) {
            $this->clientError(_m('Field not found.'));
        }

        // Delete responses to this field
        $responses = new GNUsocialProfileExtensionResponse();
        $responses->extension_id = $field->id;
        $responses->find();
        $responses = $responses->fetchAll();
        foreach ($responses as $response) {
            $response->delete();
        }

        // Delete field
        $field->delete();
    }

    public function saveSettings()
    {
        if ($this->arg('save')) {
            return $this->saveField();
        } elseif ($this->arg('remove')) {
            return $this->removeField();
        }

        // TRANS: Message given submitting a form with an unknown action in e-mail settings.
        throw new ClientException(_('Unexpected form submission.'));
    }
}

class ProfilefieldsAdminForm extends AdminForm
{
    public function id(): string
    {
        return 'form_profilefields_admin_panel';
    }

    public function formClass(): string
    {
        return 'form_settings';
    }

    public function action(): string
    {
        return '/admin/profilefields';
    }

    public function formData(): void
    {
        $title = null;
        $description = null;
        $type = null;
        $systemname = null;
        $id = null;
        $fieldsettitle = _m("New Profile Field");
        // Edit a field
        if ($this->out->trimmed('edit')) {
            $field = GNUsocialProfileExtensionField::getKV('id', $this->out->trimmed('edit'));
            $title = $field->title;
            $description = $field->description;
            $type = $field->type;
            $systemname = $field->systemname;
            $this->out->hidden('id', $field->id, 'id');
            $fieldsettitle = _m("Edit Profile Field");
        } // Don't show the list of all fields when editing one
        else {
            $this->out->elementStart('fieldset');
            $this->out->element('legend', null, _m('Existing Custom Profile Fields'));
            $this->out->elementStart('ul', 'form_data');
            $fields = GNUsocialProfileExtensionField::allFields();
            foreach ($fields as $field) {
                $this->li();
                $this->out->elementStart('div');
                $this->out->element(
                    'a',
                    array('href' => '/admin/profilefields?edit=' . $field->id),
                    $field->title
                );
                $this->out->text(' (' . $field->type . '): ' . $field->description);
                $this->out->elementEnd('div');
                $this->unli();
            }
            $this->out->elementEnd('ul');
            $this->out->elementEnd('fieldset');
        }

        // New fields
        $this->out->elementStart('fieldset');
        $this->out->element('legend', null, $fieldsettitle);
        $this->out->elementStart('ul', 'form_data');

        $this->li();
        $this->out->input(
            'title',
            _m('Title'),
            $title,
            _m('The title of the field')
        );
        $this->unli();
        $this->li();
        $this->out->input(
            'systemname',
            _m('Internal name'),
            $systemname,
            _m('The alphanumeric name used internally for this field.  Also the key used for federation (e.g. ActivityPub and OStatus) user info.')
        );
        $this->unli();
        $this->li();
        $this->out->input(
            'description',
            _m('Description'),
            $description,
            _m('An optional more detailed description of the field')
        );
        $this->unli();
        $this->li();
        $this->out->dropdown(
            'type',
            _m('Type'),
            array('text' => _m("Text"),
                'str' => _m("String")),
            _m('The type of the datafield'),
            false,
            $type
        );
        $this->unli();
        $this->out->elementEnd('ul');
        $this->out->elementEnd('fieldset');
    }

    /**
     * Action elements
     *
     * @return void
     * @throws Exception
     */
    public function formActions(): void
    {
        $this->out->submit('save', _m('BUTTON', 'Save'), 'submit', null, _m('Save field'));
        if ($this->out->trimmed('edit')) {
            $this->out->submit('remove', _m('BUTTON', 'Remove'), 'submit', null, _m('Remove field'));
        }
    }
}
