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
 * Form action extendable class.
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Form action extendable class
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <evan@status.net>
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class FormAction extends ManagedAction
{
    protected $form = null;
    protected $formOpts = array();
    protected $type = null;
    protected $needLogin = true;
    protected $canPost = true;

    protected function prepare(array $args = [])
    {
        parent::prepare($args);

        $this->form = $this->form ?: ucfirst($this->action);
        $this->args['form'] = $this->form;

        $this->type = !is_null($this->type) ? $this->type : $this->trimmed('type');
        $this->args['context'] = $this->trimmed('action');  // reply for notice for example

        if (!$this->type) {
            $this->type = null;
        }

        return true;
    }

    public function isReadOnly($args)
    {
        return !$this->isPost();
    }

    public function showPageNotice()
    {
        $this->showInstructions();
        if (!empty($msg = $this->getError())) {
            $this->element('div', 'error', $msg);
        }
        if (!empty($msg = $this->getInfo())) {
            $this->element('div', 'info', $msg);
        }
    }

    /**
     * Outputs the instructions for the form
     *
     * SHOULD overload
     */
    public function showInstructions()
    {
        // instructions are nice, so users know what to do
        $this->raw(common_markup_to_html($this->getInstructions()));
    }

    /**
     * @return string with instructions to pass into common_markup_to_html()
     */
    protected function getInstructions()
    {
        return null;
    }

    public function showForm($msg=null, $success=false)
    {
        $this->msg = $msg;
        $this->success = $success;
        $this->showPage();
    }

    protected function showContent()
    {
        $form = $this->getForm();
        $form->show();
    }

    protected function getForm()
    {
        $class = $this->form.'Form';
        $form = new $class($this, $this->formOpts);
        return $form;
    }
}
