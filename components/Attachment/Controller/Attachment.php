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

namespace Component\Attachment\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\GSFile;
use function App\Core\I18n\_m;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoSuchFileException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\ServerException;
use Component\Attachment\Entity\AttachmentThumbnail;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;

class Attachment extends Controller
{
    /**
     * Generic function that handles getting a representation for an attachment
     */
    private function attachment(int $attachment_id, Note|int $note, callable $handle)
    {
        $attachment = DB::findOneBy('attachment', ['id' => $attachment_id]);
        $note       = \is_int($note) ? Note::getById($note) : $note;

        // Before anything, ensure proper scope
        if (!$note->isVisibleTo(Common::actor())) {
            throw new ClientException(_m('You don\'t have permissions to view this attachment.'), 401);
        }

        $res = null;
        if (Event::handle('AttachmentFileInfo', [$attachment, $note, &$res]) !== Event::stop) {
            // If no one else claims this attachment, use the default representation
            try {
                $res = GSFile::getAttachmentFileInfo($attachment_id);
            } catch (NoSuchFileException $e) {
                // Continue below
            }
        }

        if (empty($res)) {
            throw new ClientException(_m('No such attachment'), 404);
        } else {
            if (!\array_key_exists('filepath', $res)) {
                // @codeCoverageIgnoreStart
                throw new ServerException('This attachment is not stored locally.');
            // @codeCoverageIgnoreEnd
            } else {
                $res['attachment'] = $attachment;
                $res['note']       = $note;
                $res['title']      = $attachment->getBestTitle($note);
                return $handle($res);
            }
        }
    }

    /**
     * The page where the attachment and it's info is shown
     */
    public function attachmentShowWithNote(Request $request, int $note_id, int $attachment_id)
    {
        try {
            return $this->attachment($attachment_id, $note_id, function ($res) use ($note_id, $attachment_id) {
                return [
                    '_template'        => '/cards/attachments/view.html.twig',
                    'download'         => $res['attachment']->getDownloadUrl(note: $note_id),
                    'title'            => $res['title'],
                    'attachment'       => $res['attachment'],
                    'note'             => $res['note'],
                    'right_panel_vars' => ['attachment_id' => $attachment_id, 'note_id' => $note_id],
                ];
            });
        } catch (NotFoundException) {
            throw new ClientException(_m('No such attachment.'), 404);
        }
    }

    /**
     * Display the attachment inline
     */
    public function attachmentViewWithNote(Request $request, int $note_id, int $attachment_id)
    {
        return $this->attachment(
            $attachment_id,
            $note_id,
            fn (array $res) => GSFile::sendFile(
                $res['filepath'],
                $res['mimetype'],
                GSFile::ensureFilenameWithProperExtension($res['title'], $res['mimetype']) ?? $res['filename'],
                HeaderUtils::DISPOSITION_INLINE,
            ),
        );
    }

    public function attachmentDownloadWithNote(Request $request, int $note_id, int $attachment_id)
    {
        return $this->attachment(
            $attachment_id,
            $note_id,
            fn (array $res) => GSFile::sendFile(
                $res['filepath'],
                $res['mimetype'],
                GSFile::ensureFilenameWithProperExtension($res['title'], $res['mimetype']) ?? $res['filename'],
                HeaderUtils::DISPOSITION_ATTACHMENT,
            ),
        );
    }

    /**
     * Controller to produce a thumbnail for a given attachment id
     *
     * @param int $attachment_id Attachment ID
     *
     * @throws \App\Util\Exception\DuplicateFoundException
     * @throws ClientException
     * @throws NotFoundException
     * @throws ServerException
     */
    public function attachmentThumbnailWithNote(Request $request, int $note_id, int $attachment_id, string $size = 'small'): Response
    {
        // Before anything, ensure proper scope
        if (!Note::getById($note_id)->isVisibleTo(Common::actor())) {
            throw new ClientException(_m('You don\'t have permissions to view this thumbnail.'), 401);
        }

        $attachment = DB::findOneBy('attachment', ['id' => $attachment_id]);

        $crop = Common::config('thumbnail', 'smart_crop');

        $thumbnail = AttachmentThumbnail::getOrCreate(attachment: $attachment, size: $size, crop: $crop);
        if (\is_null($thumbnail)) {
            throw new ClientException(_m('Can not generate thumbnail for attachment with id={id}', ['id' => $attachment->getId()]));
        }

        $filename = $thumbnail->getFilename();
        $path     = $thumbnail->getPath();
        $mimetype = $thumbnail->getMimetype();

        return GSFile::sendFile(filepath: $path, mimetype: $mimetype, output_filename: $filename . '.' . MimeTypes::getDefault()->getExtensions($mimetype)[0], disposition: HeaderUtils::DISPOSITION_INLINE);
    }
}
