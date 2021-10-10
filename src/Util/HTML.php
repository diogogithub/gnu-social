<?php

declare(strict_types = 1);

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
    public const ALLOWED_TAGS         = ['p', 'br', 'a', 'span'];
    public const FORBIDDEN_ATTRIBUTES = [
        'onerror', 'form', 'onforminput', 'onbeforescriptexecute', 'formaction', 'onfocus', 'onload',
        'data', 'event', 'autofocus', 'onactivate', 'onanimationstart', 'onwebkittransitionend', 'onblur', 'poster',
        'onratechange', 'ontoggle', 'onscroll', 'actiontype', 'dirname', 'srcdoc',
    ];

    /**
     * Creates an HTML tag without attributes
     */
    public static function tag(string $tag, mixed $attrs = null, mixed $content = null, array $options = []): array
    {
        return self::attr_tag($tag, $attrs ?? '', $content ?? '', $options);
    }

    /**
     * Create tag, possibly with attributes and indentation
     */
    private static function attr_tag(string $tag, mixed $attrs, mixed $content = '', array $options = []): array
    {
        $html = '<' . $tag . (\is_string($attrs) ? ($attrs ? ' ' : '') . $attrs : self::attr($attrs, $options));
        if ($options['empty'] ?? false) {
            $html .= '/>';
        } else {
            if ($options['indent'] ?? true) {
                $inner = Formatting::indent($content);
                $html .= ">\n" . ($inner == '' ? '' : $inner . "\n") . "</{$tag}>";
            } else {
                $html .= ">{$content}</{$tag}>";
            }
        }
        return explode("\n", $html);
    }

    /**
     * Attribute with given optional value
     */
    private static function attr(array $attrs, array $options = []): string
    {
        return ' ' . implode(
            ' ',
            F\map(
                $attrs,
                                   /**
                                    * Convert an attr ($key), $val pair to an HTML attribute, but validate to exclude some vectors of injection
                                    */
                                   function (string $val, string $key, array $_): string {
                                       if (\in_array($key, array_merge($options['forbidden_attributes'] ?? [], self::FORBIDDEN_ATTRIBUTES))
                                           || str_starts_with($val, 'javascript:')) {
                                           throw new InvalidArgumentException("HTML::html: Attribute {$key} is not allowed");
                                       }
                                       if (!($options['raw'] ?? false)) {
                                           $val = htmlspecialchars($val, flags: \ENT_QUOTES | \ENT_SUBSTITUTE, double_encode: false);
                                       }
                                       return "{$key}=\"{$val}\"";
                                   },
            ),
        );
    }

    /**
     * @param array|string $html    The input to convert to HTML
     * @param array        $options = [] ['allowed_tags' => string[], 'forbidden_attributes' => string[], 'raw' => bool, 'indent' => bool]
     */
    public static function html(string|array $html, array $options = [], int $indent = 1): string
    {
        if (\is_string($html)) {
            if ($options['raw'] ?? false) {
                return $html;
            } else {
                return htmlspecialchars($html, flags: \ENT_QUOTES | \ENT_SUBSTITUTE, double_encode: false);
            }
        } else {
            $out = '';
            foreach ($html as $tag => $contents) {
                if ($contents['empty'] ?? false) {
                    $out .= "<{$tag}/>";
                } else {
                    $attrs  = isset($contents['attrs']) ? self::attr(array_shift($contents), $options) : '';
                    $is_tag = preg_match('/[A-Za-z][A-Za-z0-9]*/', $tag);
                    $inner  = self::html($contents, $options, $indent + 1);
                    if ($is_tag) {
                        if (!\in_array($tag, array_merge($options['allowed_tags'] ?? [], self::ALLOWED_TAGS))) {
                            throw new InvalidArgumentException("HTML::html: Tag {$tag} is not allowed");
                        }
                        if (!empty($inner)) {
                            $inner = ($options['indent'] ?? true) ? ("\n" . Formatting::indent($inner, $indent) . "\n") : $inner;
                        }
                        $out .= "<{$tag}{$attrs}>{$inner}</{$tag}>";
                    } else {
                        $out .= $inner;
                    }
                }
            }
            return $out;
        }
    }
}
