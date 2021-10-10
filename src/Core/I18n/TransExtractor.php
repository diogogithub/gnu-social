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
 * Extracts translation messages from PHP code.
 *
 * @package   GNUsocial
 * @category  I18n
 *
 * @author    Symfony project
 * @author    Michel Salib <michelsalib@hotmail.com>
 * @author    Fabien Potencier <fabien@symfony.com>
 * @copyright 2011-2019 Symfony project
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core\I18n;

use App\Util\Formatting;
use ArrayIterator;
use InvalidArgumentException;
use Iterator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\AbstractFileExtractor;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\Extractor\PhpStringTokenParser;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Since this happens outside the normal request life-cycle (through a
 * command, usually), it unfeasible to test this
 *
 * @codeCoverageIgnore
 */
class TransExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    /**
     * The sequence that captures translation messages.
     *
     * @todo add support for all the cases we use
     */
    protected array $sequences = [
        // [
        //     '_m',
        //     '(',
        //     self::MESSAGE_TOKEN,
        //     ',',
        //     self::METHOD_ARGUMENTS_TOKEN,
        //     ',',
        //     self::DOMAIN_TOKEN,
        // ],
        [
            '_m',
            '(',
            self::MESSAGE_TOKEN,
        ],
        [
            // Special case: when we have calls to _m with a dynamic
            // value, we need to handle them seperately
            'function',
            '_m_dynamic',
            self::M_DYNAMIC,
        ],
    ];

    // TODO probably shouldn't be done this way
    // {{{Code from PhpExtractor
    // See vendor/symfony/translation/Extractor/PhpExtractor.php
    //
    public const MESSAGE_TOKEN          = 300;
    public const METHOD_ARGUMENTS_TOKEN = 1000;
    public const DOMAIN_TOKEN           = 1001;
    public const M_DYNAMIC              = 1002;

    /**
     * Prefix for new found message.
     */
    private string $prefix = '';

    /**
     * {@inheritDoc}
     */
    public function extract($resource, MessageCatalogue $catalog)
    {
        if (($dir = mb_strstr($resource, '/Core/GNUsocial.php', true)) === false) {
            return;
        }

        $files = $this->extractFiles($dir);
        foreach ($files as $file) {
            $this->parseTokens(token_get_all(file_get_contents($file)), $catalog, $file);

            gc_mem_caches();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Normalizes a token.
     */
    protected function normalizeToken($token): ?string
    {
        if (isset($token[1]) && 'b"' !== $token) {
            return $token[1];
        }

        return $token;
    }

    /**
     * Seeks to a non-whitespace token.
     */
    private function seekToNextRelevantToken(Iterator $tokenIterator)
    {
        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();
            if (\T_WHITESPACE !== $t[0]) {
                break;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    private function skipMethodArgument(Iterator $tokenIterator)
    {
        $openBraces = 0;

        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();

            if ('[' === $t[0] || '(' === $t[0]) {
                ++$openBraces;
            }

            if (']' === $t[0] || ')' === $t[0]) {
                --$openBraces;
            }

            if ((0 === $openBraces && ',' === $t[0]) || (-1 === $openBraces && ')' === $t[0])) {
                break;
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function canBeExtracted(string $file): bool
    {
        return $this->isFile($file)
            && 'php' === pathinfo($file, \PATHINFO_EXTENSION)
            && mb_strstr($file, '/src/') !== false;
    }

    /**
     * {@inheritDoc}
     */
    protected function extractFromDirectory($directory)
    {
        $finder = new Finder();
        return $finder->files()->name('*.php')->in($directory);
    }

    /**
     * Extracts the message from the iterator while the tokens
     * match allowed message tokens.
     */
    private function getValue(Iterator $tokenIterator)
    {
        $message  = '';
        $docToken = '';
        $docPart  = '';

        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();
            if ('.' === $t) {
                // Concatenate with next token
                continue;
            }
            if (!isset($t[1])) {
                break;
            }

            switch ($t[0]) {
                case \T_START_HEREDOC:
                    $docToken = $t[1];
                    break;
                case \T_ENCAPSED_AND_WHITESPACE:
                case \T_CONSTANT_ENCAPSED_STRING:
                    if ('' === $docToken) {
                        $message .= PhpStringTokenParser::parse($t[1]);
                    } else {
                        $docPart = $t[1];
                    }
                    break;
                case \T_END_HEREDOC:
                    $message .= PhpStringTokenParser::parseDocString($docToken, $docPart);
                    $docToken = '';
                    $docPart  = '';
                    break;
                case \T_WHITESPACE:
                    break;
                default:
                    break 2;
            }
        }

        return $message;
    }

    // }}}

    /**
     * Extracts trans message from PHP tokens.
     */
    protected function parseTokens(array $tokens, MessageCatalogue $catalog, string $filename)
    {
        $tokenIterator = new ArrayIterator($tokens);

        for ($key = 0; $key < $tokenIterator->count(); ++$key) {
            foreach ($this->sequences as $sequence) {
                $message = '';
                $domain  = I18n::_mdomain($filename);
                $tokenIterator->seek($key);

                foreach ($sequence as $sequenceKey => $item) {
                    $this->seekToNextRelevantToken($tokenIterator);

                    if ($this->normalizeToken($tokenIterator->current()) === $item) {
                        $tokenIterator->next();
                        continue;
                    } elseif (self::MESSAGE_TOKEN === $item) {
                        $message = $this->getValue($tokenIterator);

                        if (\count($sequence) === ($sequenceKey + 1)) {
                            break;
                        }
                    } elseif (self::METHOD_ARGUMENTS_TOKEN === $item) {
                        $this->skipMethodArgument($tokenIterator);
                    } elseif (self::DOMAIN_TOKEN === $item) {
                        $domainToken = $this->getValue($tokenIterator);
                        if ('' !== $domainToken) {
                            $domain = $domainToken;
                        }
                        break;
                    } elseif (self::M_DYNAMIC === $item) {
                        // Special case
                        self::storeDynamic($catalog, $filename);
                    } else {
                        break;
                    }
                }

                if ($message) {
                    self::store($catalog, $message, $domain, $filename, $tokens[$key][2]); // Line no.
                    break;
                }
            }
        }
    }

    /**
     * Store the $message in the message catalogue $mc
     */
    private function store(
        MessageCatalogue $mc,
        string $message,
        string $domain,
        string $filename,
        ?int $line_no = null,
    ) {
        $mc->set($message, $this->prefix . $message, $domain);
        $metadata              = $mc->getMetadata($message, $domain) ?? [];
        $metadata['sources'][] = Formatting::normalizePath($filename) . (!empty($line_no) ? ":{$line_no}" : '');
        $mc->setMetadata($message, $metadata, $domain);
    }

    /**
     * Calls `::_m_dynamic` from the class defined in $filename and
     * stores the results in the catalogue. For cases when the
     * translation can't be done in a static (non-PHP) file
     */
    private function storeDynamic(MessageCatalogue $mc, string $filename)
    {
        require_once $filename;
        $class   = preg_replace('/.*\/([A-Za-z]*)\.php/', '\1', $filename);
        $classes = get_declared_classes();

        // Find FQCN of $class
        foreach ($classes as $c) {
            if (mb_strstr($c, $class) !== false) {
                $class = $c;
                break;
            }
        }

        $messages = $class::_m_dynamic();
        $domain   = $messages['domain'];
        unset($messages['domain']);
        foreach ($messages as $m) {
            self::store($mc, $m, $domain, $filename);
        }
    }
}
