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
 * Low-level generator for XML
 *
 * @package   GNUsocial
 * @category  Output
 *
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
defined('GNUSOCIAL') || die();

/**
 * Low-level generator for XML
 *
 * This is a thin wrapper around PHP's XMLWriter. The main
 * advantage is the element() method, which simplifies outputting
 * an element.
 *
 * @see      Action
 * @see      HTMLOutputter
 *
 * @copyright 2008-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class xmloutputter
{
    /**
     * Wrapped XMLWriter object, which does most of the heavy lifting
     * for output.
     */
    public $xw;

    /**
     * Constructor
     *
     * Initializes the wrapped XMLWriter.
     *
     * @param null|string $output URL for outputting, if null it defaults to stdout ('php://output')
     * @param null|bool   $indent Whether to indent output, if null it defaults to true
     *
     * @throws ServerException
     */
    public function __construct($output = null, $indent = null)
    {
        if (is_null($output)) {
            $output = 'php://output';
        }
        $this->xw = new XMLWriter();
        $this->xw->openURI($output);
        if (is_null($indent)) {
            $indent = common_config('site', 'indent');
        }
        $this->xw->setIndent($indent);
    }

    /**
     * Start a new XML document
     *
     * @param string $doc    document element
     * @param string $public public identifier
     * @param string $system system identifier
     *
     * @return void
     */
    public function startXML(?string $doc = null, ?string $public = null, ?string $system = null): void
    {
        $this->xw->startDocument('1.0', 'UTF-8');
        if (!is_null($doc)) {
            $this->xw->writeDTD($doc, $public, $system);
        }
    }

    /**
     * finish an XML document
     *
     * It's probably a bad idea to continue to use this object
     * after calling endXML().
     *
     * @return void
     */
    public function endXML(): void
    {
        $this->xw->endDocument();
        $this->xw->flush();
    }

    /**
     * output an XML element
     *
     * Utility for outputting an XML element. A convenient wrapper
     * for a bunch of longer XMLWriter calls. This is best for
     * when an element doesn't have any sub-elements; if that's the
     * case, use elementStart() and elementEnd() instead.
     *
     * The $content element will be escaped for XML. If you need
     * raw output, use elementStart() and elementEnd() with a call
     * to raw() in the middle.
     *
     * If $attrs is a string instead of an array, it will be treated
     * as the class attribute of the element.
     *
     * @param string            $tag     Element type or tagname
     * @param null|array|string $attrs   Array of element attributes, as key-value pairs
     * @param null|string       $content string content of the element
     *
     * @return void
     */
    public function element(string $tag, $attrs = null, ?string $content = null): void
    {
        $this->elementStart($tag, $attrs);
        if (!is_null($content)) {
            $this->xw->text($content);
        }
        $this->elementEnd($tag);
    }

    /**
     * output a start tag for an element
     *
     * Mostly used for when an element has content that's
     * not a simple string.
     *
     * If $attrs is a string instead of an array, it will be treated
     * as the class attribute of the element.
     *
     * @param string            $tag   Element type or tagname
     * @param null|array|string $attrs Attributes
     *
     * @return void
     */
    public function elementStart(string $tag, $attrs = null): void
    {
        $this->xw->startElement($tag);
        if (is_array($attrs)) {
            foreach ($attrs as $name => $value) {
                $this->xw->writeAttribute($name, $value);
            }
        } elseif (is_string($attrs)) {
            $this->xw->writeAttribute('class', $attrs);
        }
    }

    /**
     * output an end tag for an element
     *
     * Used in conjunction with elementStart(). $tag param
     * should match the elementStart() param.
     *
     * For HTML 4 compatibility, this method will force
     * a full end element (</tag>) even if the element is
     * empty, except for a handful of exception tagnames.
     * This is a hack.
     *
     * @param string $tag Element type or tagname.
     *
     * @return void
     */
    public function elementEnd(string $tag): void
    {
        static $empty_tag = ['base', 'meta', 'link', 'hr',
            'br', 'param', 'img', 'area',
            'input', 'col', 'source', ];
        // XXX: check namespace
        if (in_array($tag, $empty_tag)) {
            $this->xw->endElement();
        } else {
            $this->xw->fullEndElement();
        }
    }

    /**
     * @param array             $ns
     * @param string            $tag
     * @param null|array|string $attrs
     * @param null|string       $content
     *
     * @return void
     */
    public function elementNS(array $ns, string $tag, $attrs = null, ?string $content = null): void
    {
        $this->elementStartNS($ns, $tag, $attrs);
        if (!is_null($content)) {
            $this->xw->text($content);
        }
        $this->elementEnd($tag);
    }

    /**
     * @param array             $ns
     * @param string            $tag
     * @param null|array|string $attrs
     *
     * @return void
     */
    public function elementStartNS(array $ns, string $tag, $attrs = null): void
    {
        reset($ns); // array pointer to 0
        $uri = key($ns);
        $this->xw->startElementNS($ns[$uri], $tag, $uri);
        if (is_array($attrs)) {
            foreach ($attrs as $name => $value) {
                $this->xw->writeAttribute($name, $value);
            }
        } elseif (is_string($attrs)) {
            $this->xw->writeAttribute('class', $attrs);
        }
    }

    /**
     * output plain text
     *
     * Text will be escaped. If you need it not to be,
     * use raw() instead.
     *
     * @param string $txt Text to output.
     *
     * @return void
     */
    public function text(string $txt): void
    {
        $this->xw->text($txt);
    }

    /**
     * output raw xml
     *
     * This will spit out its argument verbatim -- no escaping is
     * done.
     *
     * @param string $xml XML to output.
     *
     * @return void
     */
    public function raw(string $xml): void
    {
        $this->xw->writeRaw($xml);
    }

    /**
     * output a comment
     *
     * @param string $txt text of the comment
     *
     * @return void
     */
    public function comment(string $txt): void
    {
        $this->xw->writeComment($txt);
    }

    /**
     * Flush output buffers
     *
     * @return void
     */
    public function flush(): void
    {
        $this->xw->flush();
    }
}
