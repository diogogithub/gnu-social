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
 * Generator for in-memory XML
 *
 * @package   GNUsocial
 * @category  Output
 *
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
defined('GNUSOCIAL') || die();

/**
 * Create in-memory XML
 *
 * @see      Action
 * @see      HTMLOutputter
 *
 * @copyright 2009-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class xmlstringer extends XMLOutputter
{
    /**
     * XMLStringer constructor.
     *
     * @param bool $indent
     */
    public function __construct(bool $indent = false)
    {
        $this->xw = new XMLWriter();
        $this->xw->openMemory();
        $this->xw->setIndent($indent);
    }

    /**
     * @param string            $tag     Element type or tagname
     * @param null|array|string $attrs   Array of element attributes, as key-value pairs
     * @param null|string       $content string content of the element
     *
     * @return string
     */
    public static function estring(string $tag, $attrs = null, ?string $content = null): string
    {
        $xs = new self();
        $xs->element($tag, $attrs, $content);
        return $xs->getString();
    }

    /**
     * Utility for quickly creating XML-strings
     *
     * @return string
     */
    public function getString()
    {
        return $this->xw->outputMemory();
    }
}
