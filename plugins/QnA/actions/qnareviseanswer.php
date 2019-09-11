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
 * Revise an answer
 *
 * @category  QnA
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Revise an answer
 *
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class QnareviseanswerAction extends Action
{
    protected $user = null;
    protected $error = null;
    protected $question = null;
    protected $answer = null;
    protected $content = null;

    /**
     * Returns the title of the action
     *
     * @return string Action title
     */
    public function title()
    {
        // TRANS: Page title for revising a question
        return _m('Revise answer');
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
            // TRANS: Client exception thrown trying to answer a question while not logged in.
                _m("You must be logged in to answer to a question."),
                403
            );
        }

        $id = substr($this->trimmed('id'), 7);

        $this->answer = QnA_Answer::getKV('id', $id);
        $this->question = $this->answer->getQuestion();

        if (empty($this->answer) || empty($this->question)) {
            throw new ClientException(
            // TRANS: Client exception thrown trying to respond to a non-existing question.
                _m('Invalid or missing answer.'),
                404
            );
        }

        $this->answerText = $this->trimmed('answer');

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
            $this->checkSessionToken();
            if ($this->arg('revise')) {
                $this->showContent();
                return;
            } elseif ($this->arg('best')) {
                if ($this->user->id == $this->question->profile_id) {
                    $this->markBest();
                    return;
                }
            } else {
                $this->reviseAnswer();
                return;
            }
        }

        $this->showPage();
    }

    /**
     * Show the revise answer form
     *
     * @return void
     */
    public function showContent()
    {
        if (!empty($this->error)) {
            $this->element('p', 'error', $this->error);
        }

        if ($this->boolean('ajax')) {
            $this->showAjaxReviseForm();
        } else {
            $form = new QnareviseanswerForm($this->answer, $this);
            $form->show();
        }

        return;
    }

    public function showAjaxReviseForm()
    {
        $this->startHTML('text/xml;charset=utf-8');
        $this->elementStart('head');
        // TRANS: Form title for sending an answer.
        $this->element('title', null, _m('TITLE', 'Answer'));
        $this->elementEnd('head');
        $this->elementStart('body');
        $form = new QnareviseanswerForm($this->answer, $this);
        $form->show();
        $this->elementEnd('body');
        $this->endHTML();
    }

    /**
     * Mark the answer as the "best" answer
     *
     * @return void
     */
    public function markBest()
    {
        $question = $this->question;
        $answer = $this->answer;

        try {
            // close the question to further answers
            $orig = clone($question);
            $question->closed = true;
            $result = $question->update($orig);

            // mark this answer an the best answer
            $orig = clone($answer);
            $answer->best = true;
            $result = $answer->update($orig);
        } catch (ClientException $ce) {
            $this->error = $ce->getMessage();
            $this->showPage();
            return;
        }
        if ($this->boolean('ajax')) {
            common_debug("ajaxy part");
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            // TRANS: Page title after sending an answer.
            $this->element('title', null, _m('Answer'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $form = new QnashowanswerForm($this, $answer);
            $form->show();
            $this->elementEnd('body');
            $this->endHTML();
        } else {
            common_redirect($this->answer->getUrl(), 303);
        }
    }

    /**
     * Revise the answer
     *
     * @return void
     */
    public function reviseAnswer()
    {
        $answer = $this->answer;

        try {
            $orig = clone($answer);
            $answer->content = $this->answerText;
            $answer->revisions++;
            $result = $answer->update($orig);
        } catch (ClientException $ce) {
            $this->error = $ce->getMessage();
            $this->showPage();
            return;
        }
        if ($this->boolean('ajax')) {
            common_debug("ajaxy part");
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            // TRANS: Page title after sending an answer.
            $this->element('title', null, _m('Answer'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $form = new QnashowanswerForm($this, $answer);
            $form->show();
            $this->elementEnd('body');
            $this->endHTML();
        } else {
            common_redirect($this->answer->getUrl(), 303);
        }
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
