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

namespace Plugin\StoreRemoteMedia;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\GSFile;
use App\Core\HTTPClient;
use App\Core\Modules\Plugin;
use App\Entity\AttachmentThumbnail;
use App\Entity\AttachmentToLink;
use App\Entity\AttachmentToNote;
use App\Entity\Link;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\ServerException;
use App\Util\Exception\TemporaryFileException;
use App\Util\TemporaryFile;

/**
 * The StoreRemoteMedia plugin downloads remotely attached files to local server.
 *
 * @package   GNUsocial
 *
 * @author    Mikael Nordfeldth
 * @author    Stephen Paul Weber
 * @author    Mikael Nordfeldth
 * @author    Miguel Dantas
 * @author    Diogo Peralta Cordeiro
 * @copyright 2015-2016, 2019-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class StoreRemoteMedia extends Plugin
{
    public function version(): string
    {
        return '3.0.0';
    }

    public bool $store_original = false; // Whether to maintain a copy of the original media or only a thumbnail of it
    public ?int $thumbnail_width;
    public ?int $thumbnail_height;
    public ?int $max_size;
    public ?bool $smart_crop;

    private function getStoreOriginal(): bool
    {
        return $this->store_original;
    }
    private function getThumbnailWidth(): int
    {
        return $this->thumbnail_width ?? Common::config('thumbnail', 'width');
    }

    private function getThumbnailHeight(): int
    {
        return $this->thumbnail_height ?? Common::config('thumbnail', 'height');
    }

    private function getMaxSize(): int
    {
        return $this->max_size ?? Common::config('attachments', 'file_quota');
    }

    private function getSmartCrop(): bool
    {
        return $this->smart_crop ?? Common::config('thumbnail', 'smart_crop');
    }

    /**
     * @param Link $link
     * @param Note $note
     *
     * @throws DuplicateFoundException
     * @throws ServerException
     * @throws TemporaryFileException
     *
     * @return bool
     */
    public function onNewLinkFromNote(Link $link, Note $note): bool
    {
        // Embed is the plugin to handle these
        if ($link->getMimetypeMajor() === 'text') {
            return Event::next;
        }

        // Have we handled it already?
        $attachment_to_link = DB::find('attachment_to_link',
            ['link_id' => $link->getId()]);

        // If it was handled already
        if (!is_null($attachment_to_link)) {
            // Relate the note with the existing attachment
            DB::persist(AttachmentToNote::create([
                'attachment_id' => $attachment_to_link->getAttachmentId(),
                'note_id'       => $note->getId(),
            ]));
            DB::flush();
            return Event::stop;
        } else {
            // Retrieve media
            $get_response = HTTPClient::get($link->getUrl());
            $media        = $get_response->getContent();
            $mimetype     = $get_response->getHeaders()['content-type'][0];
            unset($get_response);

            // Ensure we still want to handle it
            if ($mimetype != $link->getMimetype()) {
                $link->setMimetype($mimetype);
                DB::persist($link);
                DB::flush();
                if ($link->getMimetypeMajor() === 'text') {
                    return Event::next;
                }
            }

            // Create an attachment for this
            $temp_file = new TemporaryFile();
            $temp_file->write($media);
            $attachment = GSFile::sanitizeAndStoreFileAsAttachment($temp_file);

            // Relate the link with the attachment
            DB::persist(AttachmentToLink::create([
                'link_id'       => $link->getId(),
                'attachment_id' => $attachment->getId(),
            ]));

            // Relate the note with the attachment
            DB::persist(AttachmentToNote::create([
                'attachment_id' => $attachment->getId(),
                'note_id'       => $note->getId(),
            ]));

            DB::flush();

            // Should we create a thumb and delete the original file?
            if (!$this->getStoreOriginal()) {
                $thumbnail = AttachmentThumbnail::getOrCreate(
                    attachment: $attachment,
                    width: $this->getThumbnailWidth(),
                    height: $this->getThumbnailHeight(),
                    crop: $this->getSmartCrop()
                );
                $attachment->deleteStorage();
            }

            return Event::stop;
        }
    }

    /**
     * Event raised when GNU social polls the plugin for information about it.
     * Adds this plugin's version information to $versions array
     *
     * @param &$versions array inherited from parent
     *
     * @return bool true hook value
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name'        => 'StoreRemoteMedia',
            'version'     => $this->version(),
            'author'      => 'Mikael Nordfeldth, Diogo Peralta Cordeiro',
            'homepage'    => GNUSOCIAL_PROJECT_URL,
            'description' => // TRANS: Plugin description.
                _m('Plugin for downloading remotely attached files to local server.'),
        ];
        return Event::next;
    }
}
