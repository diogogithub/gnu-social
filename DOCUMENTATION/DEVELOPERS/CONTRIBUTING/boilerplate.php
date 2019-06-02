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
 * Description of this file.
 *
 * @package   samples
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace samples;

defined('GNUSOCIAL') || die();

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'SampleHandler.php');

/**
 * Description of this class.
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class MySampleClass
{
    /**
     * Constructor for the sample class.
     *
     * @param string $dummy_word just because.
     * @param int $result another just because.
     */
    public function __construct(string $dummy_word = '', int $result = null)
    {
        global $demo;
        $this->niceWorld();
    }

    /**
     * How cool is this function.
     *
     * @return string
     */
    public function niceWorld() : string
    {
        return 'hello, world.';
    }
}
