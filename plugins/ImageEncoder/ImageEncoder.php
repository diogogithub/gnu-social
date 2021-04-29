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

namespace Plugin\ImageEncoder;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\GSFile;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Modules\Plugin;
use App\Entity\Attachment;
use App\Entity\AttachmentThumbnail;
use App\Util\Common;
use App\Util\TemporaryFile;
use Exception;
use Jcupitt\Vips;

class ImageEncoder extends Plugin
{
    /**
     * Several obscure file types should be normalized to WebP on resize.
     */
    public function preferredType(): int
    {
        return IMAGETYPE_WEBP;
    }

    /**
     * Encodes the image to self::preferredType() format ensuring it's valid.
     *
     * @param SymfonyFile $sfile    i/o
     * @param null|string $mimetype out
     *
     * @return bool
     */
    public function onAttachmentValidation(\SplFileInfo &$file, ?string &$mimetype, ?string &$title): bool
    {
        $original_mimetype = $mimetype;
        if (GSFile::mimetypeMajor($original_mimetype) != 'image') {
            // Nothing concerning us
            return Event::next;
        }

        $type      = self::preferredType();
        $extension = image_type_to_extension($type, include_dot: true);
        $temp      = new TemporaryFile(prefix: null, suffix: $extension); // This handles deleting the file if some error occurs
        $mimetype  = image_type_to_mime_type($type);
        if ($mimetype != $original_mimetype) {
            // If title seems to be a filename with an extension
            if (preg_match('/\.[a-z0-9]/i', $title) === 1) {
                $title = substr($title, 0, strrpos($title, '.') - 1) . $extension;
            }
        }

        $image  = Vips\Image::newFromFile($file->getRealPath(), ['access' => 'sequential']);
        $width  = Common::clamp($image->width, 0, Common::config('attachments', 'max_width'));
        $height = Common::clamp($image->height, 0, Common::config('attachments', 'max_height'));
        $image  = $image->crop(0, 0, $width, $height);
        $image->writeToFile($temp->getRealPath());

        $filesize = $temp->getSize();
        $filepath = $file->getRealPath();
        @unlink($filepath);

        $file_quota = Common::config('attachments', 'file_quota');
        if ($filesize > $file_quota) {
            // TRANS: Message given if an upload is larger than the configured maximum.
            throw new ClientException(_m('No file may be larger than {quota} bytes and the file you sent was {size} bytes. ' .
                                         'Try to upload a smaller version.', ['quota' => $file_quota, 'size' => $filesize]));
        }

        $user  = Common::user();
        $query = <<<END
select sum(at.size) as total
    from attachment at
        join attachment_to_note an with at.id = an.attachment_id
        join note n with an.note_id = n.id
    where n.gsactor_id = :actor_id and at.size is not null
END;

        $user_quota = Common::config('attachments', 'user_quota');
        if ($user_quota != false) {
            $cache_key_user_total = 'user-' . $user->getId() . 'file-quota';
            $user_total           = Cache::get($cache_key_user_total, fn () => DB::dql($query, ['actor_id' => $user->getId()])[0]['total']);
            Cache::set($cache_key_user_total, $user_total + $filesize);

            if ($user_total + $filesize > $user_quota) {
                // TRANS: Message given if an upload would exceed user quota.
                throw new ClientException(_m('A file this large would exceed your user quota of {quota} bytes.', ['quota' => $user_quota]));
            }
        }

        $query .= ' AND MONTH(at.modified) = MONTH(CURRENT_DATE())'
                . ' AND YEAR(at.modified)  = YEAR(CURRENT_DATE())';

        $monthly_quota = Common::config('attachments', 'monthly_quota');
        if ($monthly_quota != false) {
            $cache_key_user_monthly = 'user-' . $user->getId() . 'monthly-file-quota';
            $monthly_total          = Cache::get($cache_key_user_monthly, fn () => DB::dql($query, ['actor_id' => $user->getId()])[0]['total']);
            Cache::set($cache_key_user_monthly, $monthly_total + $filesize);

            if ($monthly_total + $filesize > $monthly_quota) {
                // TRANS: Message given if an upload would exceed user quota.
                throw new ClientException(_m('A file this large would exceed your monthly quota of {quota} bytes.', ['quota' => $monthly_quota]));
            }
        }

        $temp->commit($filepath);

        return Event::stop;
    }

    public function onResizeImage(Attachment $attachment, AttachmentThumbnail $thumbnail, int $width, int $height, bool $smart_crop): bool
    {
        return $this->onResizeImagePath($attachment->getPath(), $thumbnail->getPath(), $width, $height, $smart_crop, $__mimetype);
    }

    /**
     * Resizes an image. It will encode the image in the
     * `self::preferredType()` format. This only applies henceforward,
     * not retroactively
     *
     * Increases the 'memory_limit' to the one in the 'attachments' section in the config, to
     * enable the handling of bigger images, which can cause a peak of memory consumption, while
     * encoding
     *
     * @param Attachment          $attachment
     * @param AttachmentThumbnail $thumbnail
     * @param int                 $width
     * @param int                 $height
     * @param bool                $crop
     *
     * @throws Exception
     * @throws Vips\Exception
     *
     * @return bool
     *
     */
    public function onResizeImagePath(string $source, string $destination, int $width, int $height, bool $smart_crop, ?string &$mimetype)
    {
        $old_limit = ini_set('memory_limit', Common::config('attachments', 'memory_limit'));
        try {
            try {
                $image = Vips\Image::thumbnail($source, $width, ['height' => $height]);
            } catch (Exception $e) {
                Log::error(__METHOD__ . ' encountered exception: ' . print_r($e, true));
                // TRANS: Exception thrown when trying to resize an unknown file type.
                throw new Exception(_m('Unknown file type'));
            }

            if ($source === $destination) {
                @unlink($destination);
            }

            $type     = self::preferredType();
            $mimetype = image_type_to_mime_type($type);

            if ($smart_crop) {
                $image = $image->smartcrop($width, $height);
            }
            $image->writeToFile($destination);
            unset($image);
        } finally {
            ini_set('memory_limit', $old_limit); // Restore the old memory limit
        }
        return Event::next;
    }
}
