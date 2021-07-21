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

namespace App\Util;

use App\Util\Exception\TemporaryFileException;
use Symfony\Component\Mime\MimeTypes;

/**
 * Class oriented at providing automatic temporary file handling.
 *
 * @package   GNUsocial
 *
 * @author    Alexei Sorokin <sor.alexei@meowr.ru>
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class TemporaryFile extends \SplFileInfo
{
    protected $resource;

    /**
     * @param array $options - ['prefix' => ?string, 'suffix' => ?string, 'mode' => ?string, 'directory' => ?string]
     *                       Description of options:
     *                       > prefix: The file name will begin with that prefix, default is 'gs-php'
     *                       > suffix: The file name will end with that suffix, default is ''
     *                       > mode: Operation mode, default is 'w+b'
     *                       > directory: Directory where the file will be used, default is the system's temporary
     *
     * @throws TemporaryFileException
     */
    public function __construct(array $options = [])
    {
        $attempts = 16;
        $filename = uniqid(($options['directory'] ?? (sys_get_temp_dir() . '/')) . ($options['prefix'] ?? 'gs-php')) . ($options['suffix'] ?? '');
        for ($count = 0; $count < $attempts; ++$count) {
            $this->resource = @fopen($filename, $options['mode'] ?? 'w+b');
            if ($this->resource !== false) {
                break;
            }
        }
        if ($count == $attempts && $this->resource !== false) {
            // @codeCoverageIgnoreStart
            $this->cleanup();
            throw new TemporaryFileException('Could not open file: ' . $filename);
            // @codeCoverageIgnoreEnd
        }

        parent::__construct($filename);
    }

    public function __destruct()
    {
        $this->close();
        $this->cleanup();
    }

    /**
     * Binary-safe file write
     *
     * @see https://php.net/manual/en/function.fwrite.php
     *
     * @param string $data The string that is to be written.
     *
     * @return null|false|int the number of bytes written, false on error, null on null resource/stream
     */
    public function write(string $data): int | false | null
    {
        if (!is_null($this->resource)) {
            return fwrite($this->resource, $data);
        } else {
            return null;
        }
    }

    /**
     * Closes the file descriptor if opened.
     *
     * @return bool true on success or false on failure.
     */
    protected function close(): bool
    {
        $ret = true;
        if (!is_null($this->resource)) {
            $ret = fclose($this->resource);
        }
        if ($ret) {
            $this->resource = null;
        }
        return $ret;
    }

    /**
     * Closes the file descriptor and removes the temporary file.
     *
     * @return void
     */
    protected function cleanup(): void
    {
        $path = $this->getRealPath();
        $this->close();
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Get the file resource.
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Release the hold on the temporary file and move it to the desired
     * location, setting file permissions in the process.
     *
     * @param string $directory Path where the file should be stored
     * @param string $filename  The filename
     * @param int    $dirmode   New directory permissions (in octal mode)
     * @param int    $filemode  New file permissions (in octal mode)
     *
     * @throws TemporaryFileException
     *
     * @return void
     */
    public function move(string $directory, string $filename, int $dirmode = 0755, int $filemode = 0644): void
    {
        if (!is_dir($directory)) {
            if (false === @mkdir($directory, $dirmode, true) && !is_dir($directory)) {
                throw new TemporaryFileException(sprintf('Unable to create the "%s" directory.', $directory));
            }
        } elseif (!is_writable($directory)) {
            throw new TemporaryFileException(sprintf('Unable to write in the "%s" directory.', $directory));
        }

        $destpath = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $this->getName($filename);

        $this->commit($destpath, $dirmode, $filemode);
    }

    /**
     * Release the hold on the temporary file and move it to the desired
     * location, setting file permissions in the process.
     *
     * @param string $destpath Full path of destination file
     * @param int    $dirmode  New directory permissions (in octal mode)
     * @param int    $filemode New file permissions (in octal mode)
     *
     * @throws TemporaryFileException
     *
     * @return void
     */
    public function commit(string $destpath, int $dirmode = 0755, int $filemode = 0644): void
    {
        $temppath = $this->getRealPath();

        // Might be attempted, and won't end well
        if ($destpath === $temppath) {
            throw new TemporaryFileException('Cannot use self as destination');
        }

        // Memorise if the file was there and see if there is access
        $exists = file_exists($destpath);

        if (!$this->close()) {
            // @codeCoverageIgnoreStart
            throw new TemporaryFileException('Could not close the resource');
            // @codeCoverageIgnoreEnd
        }

        set_error_handler(function ($type, $msg) use (&$error) { $error = $msg; });
        $renamed = rename($this->getPathname(), $destpath);
        restore_error_handler();
        chmod($destpath, $filemode);
        if (!$renamed) {
            if (!$exists) {
                // If the file wasn't there, clean it up in case of a later failure
                unlink($destpath);
            }
            throw new TemporaryFileException(sprintf('Could not move the file "%s" to "%s" (%s).', $this->getPathname(), $destpath, strip_tags($error)));
        }
    }

    /**
     * This function is a copy of Symfony\Component\HttpFoundation\File\File->getMimeType()
     * Returns the mime type of the file.
     *
     * The mime type is guessed using a MimeTypeGuesserInterface instance,
     * which uses finfo_file() then the "file" system binary,
     * depending on which of those are available.
     *
     * @return null|string The guessed mime type (e.g. "application/pdf")
     *
     * @see MimeTypes
     */
    public function getMimeType()
    {
        if (!class_exists(MimeTypes::class)) {
            throw new \LogicException('You cannot guess the mime type as the Mime component is not installed. Try running "composer require symfony/mime".');
        }

        return MimeTypes::getDefault()->guessMimeType($this->getPathname());
    }

    /**
     * This function is a copy of Symfony\Component\HttpFoundation\File\File->getName()
     * Returns locale independent base name of the given path.
     *
     * @return string
     */
    protected function getName(string $name)
    {
        $originalName = str_replace('\\', '/', $name);
        $pos          = strrpos($originalName, '/');
        $originalName = false === $pos ? $originalName : substr($originalName, $pos + 1);

        return $originalName;
    }
}