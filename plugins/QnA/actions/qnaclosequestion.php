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
 * Close a question to further answers
 *
 * @category  QnA
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or late
 */

defined('GNUSOCIAL') || die();

/**
 * Close a question to new answers
 *
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or late
 */
class QnaclosequestionAction extends Action
{
    protected $user = null;
    protected $error = null;
    protected $complete = null;

    protected $question = null;
    protected $answer = null;

    /**
     * Returns the title of the action
     *
     * @return string Action title
     */
    public function title()
    {
        // TRANS: Page title for close a question
        return _m('Close question');
    }

    /**
     * For initializing members of the class.
     *
     * @param array $args misc. arguments
     *
     * @return boolean true
     * @throws ClientException
     */
    public function prepare(array $args = [])
    {
        parent::prepare($args);
        if ($this->boolean('ajax')) {
            GNUsocial::setApi(true);
        }

        $this->user = common_current_user();

        if (empty($this->user)) {
            throw new ClientException(
            // TRANS: Client exception thrown trying to close a question when not logged in
                _m("You must be logged in to close a question."),
                403
            );
        }

        if ($this->isPost()) {
            $this->checkSessionToken();
        }

        $id = substr($this->trimmed('id'), 9);
        $this->question = QnA_Question::getKV('id', $id);
        if (empty($this->question)) {
            // TRANS: Client exception thrown trying to respond to a non-existing question.
            throw new ClientException(_m('Invalid or missing question.'), 404);
        }

        return true;
    }

    /**
     * Handler method
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        if ($this->isPost()) {
            $this->closeQuestion();
        } else {
            $this->showPage();
        }

        return;
    }

    /**
     * Close a question
     *
     * @return void
     */
    public function closeQuestion()
    {
        $user = common_current_user();

        try {
            if ($user->id != $this->question->profile_id) {
                // TRANS: Exception thrown trying to close another user's question.
                throw new Exception(_m('You did not ask this question.'));
            }

            $orig = clone($this->question);
            $this->question->closed = true;
            $this->question->update($orig);
        } catch (ClientException $ce) {
            $this->error = $ce->getMessage();
            $this->showPage();
            return;
        }

        if ($this->boolean('ajax')) {
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            // TRANS: Page title after sending an answer.
            $this->element('title', null, _m('Answers'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $form = new QnashowquestionForm($this, $this->question);
            $form->show();
            $this->elementEnd('body');
            $this->endHTML();
        } else {
            common_redirect($this->question->getUrl(), 303);
        }
    }

    /**
     * Show the close question form
     *
     * @return void
     */
    public function showContent()
    {
        if (!empty($this->error)) {
            $this->element('p', 'error', $this->error);
        }

        // blar
    }

    /**
     * Return true if read only.
     *
     * MAY override
     *
     * @param array $args other arguments
     *
     * @return boolean is read only action?
     */
    public function isReadOnly($args)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET' ||
            $_SERVER['REQUEST_METHOD'] == 'HEAD') {
            return true;
        } else {
            return false;
        }
    }
}
