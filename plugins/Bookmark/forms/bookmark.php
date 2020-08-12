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
 * Form for adding a new bookmark
 *
 * @category  Bookmark
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Form to add a new bookmark
 *
 * @category  Bookmark
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class BookmarkForm extends Form
{
    private $_title       = null;
    private $_url         = null;
    private $_tags        = null;
    private $_description = null;
    private $_thumbnail   = null;

    /**
     * Construct a bookmark form
     *
     * @param HTMLOutputter $out         output channel
     * @param string        $title       Title of the bookmark
     * @param string        $url         URL of the bookmark
     * @param string        $tags        Tags to show
     * @param string        $description Description of the bookmark
     *
     * @return void
     */
    public function __construct(
        $out = null,
        $title = null,
        $url = null,
        $tags = null,
        $description = null,
        $thumbnail = null
    ) {
        parent::__construct($out);

        $this->_title       = $title;
        $this->_url         = $url;
        $this->_tags        = $tags;
        $this->_description = $description;
        $this->_thumbnail   = $thumbnail;
    }

    /**
     * ID of the form
     *
     * @return int ID of the form
     */
    public function id()
    {
        return 'form_new_bookmark';
    }

    /**
     * class of the form
     *
     * @return string class of the form
     */
    public function formClass()
    {
        return 'form_settings ajax-notice';
    }

    /**
     * Action of the form
     *
     * @return string URL of the action
     */
    public function action()
    {
        return common_local_url('newbookmark');
    }

    /**
     * Data elements of the form
     *
     * @return void
     */
    public function formData()
    {
        $this->out->elementStart('fieldset', array('id' => 'new_bookmark_data'));
        $this->out->elementStart('ul', 'form_data');

        $this->li();
        $this->out->input(
            'bookmark-url',
            // TRANS: Field label on form for adding a new bookmark.
            _m('LABEL', 'URL'),
            $this->_url,
            null,
            'url',
            true  // HTML5 "required" attribute
        );
        $this->unli();

        if (!empty($this->_thumbnail)) {
            [$width, $height] = $this->scaleImage(
                $this->_thumbnail->width,
                $this->_thumbnail->height
            );

            $this->out->element('img', [
                'src'    => $this->_thumbnail->getUrl(),
                'class'  => 'bookmarkform-thumbnail',
                'width'  => $width,
                'height' => $height,
            ]);
        }

        $this->li();
        $this->out->input(
            'bookmark-title',
            // TRANS: Field label on form for adding a new bookmark.
            _m('LABEL', 'Title'),
            $this->_title,
            null,
            'title',
            true  // HTML5 "required" attribute
        );
        $this->unli();

        $this->li();
        $this->out->textarea(
            'bookmark-description',
            // TRANS: Field label on form for adding a new bookmark.
            _m('LABEL', 'Notes'),
            $this->_description,
            null,
            'description'
        );
        $this->unli();

        $this->li();
        $this->out->input(
            'bookmark-tags',
            // TRANS: Field label on form for adding a new bookmark.
            _m('LABEL', 'Tags'),
            ($this->_tags ? implode(',', $this->_tags) : null),
            // TRANS: Field title on form for adding a new bookmark.
            _m('Comma- or space-separated list of tags.'),
            'tags'
        );
        $this->unli();

        $this->out->elementEnd('ul');

        $toWidget = new ToSelector(
            $this->out,
            common_current_user(),
            null
        );
        $toWidget->show();

        $this->out->elementEnd('fieldset');
    }

    /**
     * Action elements
     *
     * @return void
     */

    public function formActions()
    {
        // TRANS: Button text for action to save a new bookmark.
        $this->out->submit('bookmark-submit', _m('BUTTON', 'Save'), 'submit', 'submit');
    }

    public function scaleImage($width, $height)
    {
        $maxwidth = common_config('thumbnail', 'width');
        $maxheight = common_config('thumbnail', 'height');

        if ($width > $height && $width > $maxwidth) {
            $height = (int) ((((float)$maxwidth)/(float)($width))*(float)$height);
            $width = $maxwidth;
        } elseif ($height > $maxheight) {
            $width = (int) ((((float)$maxheight)/(float)($height))*(float)$width);
            $height = $maxheight;
        }

        return array($width, $height);
    }
}
