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

namespace Component\Media;

use App\Core\Module;
use App\Entity\File;
use App\Util\Common;
use App\Util\Nickname;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

class Media extends Module
{
    public static function validateAndStoreFile(SymfonyFile $sfile, string $dest_dir, ?string $title = null, bool $is_local = true): File
    {
        // The following properly gets the mimetype with `file` or other
        // available methods, so should be safe
        $hash = hash_file(File::FILEHASH_ALGO, $sfile->getPathname());
        $file = File::create([
            'file_hash' => $hash,
            'mimetype'  => $sfile->getMimeType(),
            'size'      => $sfile->getSize(),
            'title'     => $title,
            'timestamp' => $sfile->getMTime(),
            'is_local'  => $is_local,
        ]);
        $sfile->move($dest_dir, $hash);
        // TODO Normalize file types
        return $file;
    }

    /**
     * Include $filepath in the response, for viewing and downloading.
     *
     * @throws ServerException
     */
    public static function sendFile(string $filepath, string $mimetype, string $output_filename, string $disposition = 'inline'): void
    {
        $x_delivery = Common::config('site', 'x_static_delivery');
        if (is_string($x_delivery)) {
            $tmp           = explode(INSTALLDIR, $filepath);
            $relative_path = end($tmp);
            Log::debug("Using Static Delivery with header for: {$relative_path}");
            header("{$x_delivery}: {$relative_path}");
        } else {
            if (file_exists($filepath)) {
                header('Content-Description: File Transfer');
                header("Content-Type: {$mimetype}");
                header("Content-Disposition: {$disposition}; filename=\"{$output_filename}\"");
                header('Expires: 0');
                header('Content-Transfer-Encoding: binary');

                $filesize = filesize($filepath);

                http_response_code(200);
                header("Content-Length: {$filesize}");
                // header('Cache-Control: private, no-transform, no-store, must-revalidate');

                $ret = @readfile($filepath);

                if ($ret === false) {
                    http_response_code(404);
                    Log::error("Couldn't read file at {$filepath}.");
                } elseif ($ret !== $filesize) {
                    http_response_code(500);
                    Log::error('The lengths of the file as recorded on the DB (or on disk) for the file ' .
                               "{$filepath} differ from what was sent to the user ({$filesize} vs {$ret}).");
                }
            }
        }
    }

    public function onAddRoute($r)
    {
        $r->connect('avatar', '/{nickname<' . Nickname::DISPLAY_FMT . '>}/avatar/{size<full|big|medium|small>?full}', [Controller\Avatar::class, 'send']);
    }
}
