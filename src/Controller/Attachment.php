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

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\GSFile;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\AttachmentThumbnail;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoSuchFileException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\ServerException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;

class Attachment extends Controller
{
    /**
     * Generic function that handles getting a representation for an attachment
     */
    private function attachment(int $id, callable $handle)
    {
        if ($id <= 0) { // This should never happen coming from the router, but let's bail if it does
            // @codeCoverageIgnoreStart
            Log::critical("Attachment controller called with {$id}, which should not be possible");
            throw new ClientException(_m('No such attachment.'), 404);
        // @codeCoverageIgnoreEnd
        } else {
            $res = null;
            if (Event::handle('AttachmentFileInfo', [$id, &$res]) != Event::stop) {
                // If no one else claims this attachment, use the default representation
                try {
                    $res = GSFile::getAttachmentFileInfo($id);
                } catch (NoSuchFileException $e) {
                    // Continue below
                }
            }
        }

        if (empty($res)) {
            throw new ClientException(_m('No such attachment'), 404);
        } else {
            if (!array_key_exists('filepath', $res)) {
                // @codeCoverageIgnoreStart
                throw new ServerException('This attachment is not stored locally.');
            // @codeCoverageIgnoreEnd
            } else {
                return $handle($res);
            }
        }
    }

    /**
     * The page where the attachment and it's info is shown
     */
    public function attachment_show(Request $request, int $id)
    {
        try {
            $attachment = DB::findOneBy('attachment', ['id' => $id]);
            return $this->attachment($id, function ($res) use ($id, $attachment) {
                return [
                    '_template'        => '/cards/attachments/show.html.twig',
                    'download'         => Router::url('attachment_download', ['id' => $id]),
                    'attachment'       => $attachment,
                    'right_panel_vars' => ['attachment_id' => $id],
                ];
            });
        } catch (NotFoundException) {
            throw new ClientException(_m('No such attachment.'), 404);
        }
    }

    /**
     * Display the attachment inline
     */
    public function attachment_view(Request $request, int $id)
    {
        return $this->attachment($id, fn (array $res) => GSFile::sendFile(
                                     $res['filepath'], $res['mimetype'],
                                     GSFile::ensureFilenameWithProperExtension($res['filename'], $res['mimetype']) ?? $res['filename'],
                                     HeaderUtils::DISPOSITION_INLINE
                                 )
        );
    }

    public function attachment_download(Request $request, int $id)
    {
        return $this->attachment($id, fn (array $res) => GSFile::sendFile(
                                     $res['filepath'], $res['mimetype'],
                                     GSFile::ensureFilenameWithProperExtension($res['filename'], $res['mimetype']) ?? $res['filename'],
                                     HeaderUtils::DISPOSITION_ATTACHMENT
                                 )
        );
    }

    /**
     * Controller to produce a thumbnail for a given attachment id
     *
     * @param Request $request
     * @param int     $id      Attachment ID
     *
     * @throws ClientException
     * @throws NotFoundException
     * @throws ServerException
     * @throws \App\Util\Exception\DuplicateFoundException
     *
     * @return Response
     */
    public function attachment_thumbnail(Request $request, int $id, string $size = 'small'): Response
    {
        $attachment = DB::findOneBy('attachment', ['id' => $id]);

        $crop = Common::config('thumbnail', 'smart_crop');

        $thumbnail = AttachmentThumbnail::getOrCreate(attachment: $attachment, size: $size, crop: $crop);
        if (is_null($thumbnail)) {
            throw new ClientException(_m('Can not generate thumbnail for attachment with id={id}', ['id' => $attachment->getId()]));
        }

        $filename = $thumbnail->getFilename();
        $path     = $thumbnail->getPath();
        $mimetype = $thumbnail->getMimetype();

        return GSFile::sendFile(filepath: $path, mimetype: $mimetype, output_filename: $filename . '.' . MimeTypes::getDefault()->getExtensions($mimetype)[0], disposition: HeaderUtils::DISPOSITION_INLINE);
    }
}
