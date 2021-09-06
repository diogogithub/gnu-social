<?php

// {{{ License
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
// }}}

/**
 * HTML Abstraction
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util;

use Functional as F;
use InvalidArgumentException;

abstract class HTML
{
    /**
     * Creates an HTML tag without attributes
     *
     * @param string       $tag
     * @param array|string $content
     * @param bool         $empty_tag
     * @param null|mixed   $attrs
     *
     * @return array
     */
    public static function tag(string $tag, $attrs = null,  $content = null, bool $empty_tag = false): array
    {
        return self::attr_tag($tag, $attrs ?? '', $content ?? '', $empty_tag);
    }

    /**
     * Create tag, possibly with attributes and indentation
     *
     * @param string       $tag
     * @param array|string $attrs     - element attributes
     * @param array|string $content   - what goes inside the tag
     * @param bool         $empty_tag
     *
     * @return array
     */
    private static function attr_tag(string $tag, $attrs, $content = '', bool $empty_tag = false): array
    {
        $html = '<' . $tag . (is_string($attrs) ? ($attrs ? ' ' : '') . $attrs : self::attr($attrs));
        if ($empty_tag) {
            $html .= '/>';
        } else {
            $inner = Formatting::indent($content);
            $html .= ">\n" . ($inner == '' ? '' : $inner . "\n") . "</{$tag}>";
        }
        return explode("\n", $html);
    }

    /**
     * Attribute with given optional value
     *
     * @param array $attrs
     *
     * @return string
     */
    private static function attr(array $attrs): string
    {
        return ' ' . implode(' ', F\map($attrs, function ($val, $key, $_) {
            return "{$key}=\"{$val}\"";
        }));
    }

    /**
     * @param mixed $html
     *
     * @return string
     */
    public static function html($html): string
    {
        if (is_string($html)) {
            return $html;
        } elseif (is_array($html)) {
            $out = '';
            foreach ($html as $tag => $contents) {
                if ($contents == 'empty' || isset($contents['empty'])) {
                    $out .= "<{$tag}/>";
                } else {
                    $attrs  = isset($contents['attrs']) ? self::attr(array_shift($contents)) : '';
                    $is_tag = preg_match('/[A-Za-z][A-Za-z0-9]*/', $tag);
                    $inner  = self::html($contents);
                    $inner  = $is_tag ? Formatting::indent($inner) : $inner;
                    $out .= $is_tag ? "<{$tag}{$attrs}>\n{$inner}\n</{$tag}>\n" : $inner;
                }
            }
            return $out;
        } else {
            throw new InvalidArgumentException('HTML::html argument must be of type string or array');
        }
    }
}
