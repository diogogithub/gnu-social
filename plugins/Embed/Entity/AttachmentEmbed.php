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
 * OembedPlugin implementation for GNU social
 *
 * @package   GNUsocial
 *
 * @author    Stephen Paul Weber
 * @author    Mikael Nordfeldth
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\Embed\Entity;

use App\Core\Entity;
use App\Core\GSFile;
use App\Core\Router\Router;
use App\Util\Common;
use DateTimeInterface;

/**
 * Table Definition for attachment_embed
 *
 * @author Hugo Sales <hugo@hsal.es>
 * @copyright 2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class AttachmentEmbed extends Entity
{
    // {{{ Autocode
    private int $attachment_id;
    private ?string $mimetype;
    private ?string $filename;
    private ?string $provider;
    private ?string $provider_url;
    private ?int $width;
    private ?int $height;
    private ?string $html;
    private ?string $title;
    private ?string $author_name;
    private ?string $author_url;
    private ?string $media_url;
    private \DateTimeInterface $modified;

    public function setAttachmentId(int $attachment_id): self
    {
        $this->attachment_id = $attachment_id;
        return $this;
    }

    public function getAttachmentId(): int
    {
        return $this->attachment_id;
    }

    public function setMimetype(?string $mimetype): self
    {
        $this->mimetype = $mimetype;
        return $this;
    }

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setProvider(?string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProviderUrl(?string $provider_url): self
    {
        $this->provider_url = $provider_url;
        return $this;
    }

    public function getProviderUrl(): ?string
    {
        return $this->provider_url;
    }

    public function setWidth(?int $width): self
    {
        $this->width = $width;
        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setHeight(?int $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHtml(?string $html): self
    {
        $this->html = $html;
        return $this;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setAuthorName(?string $author_name): self
    {
        $this->author_name = $author_name;
        return $this;
    }

    public function getAuthorName(): ?string
    {
        return $this->author_name;
    }

    public function setAuthorUrl(?string $author_url): self
    {
        $this->author_url = $author_url;
        return $this;
    }

    public function getAuthorUrl(): ?string
    {
        return $this->author_url;
    }

    public function setMediaUrl(?string $media_url): self
    {
        $this->media_url = $media_url;
        return $this;
    }

    public function getMediaUrl(): ?string
    {
        return $this->media_url;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    // }}} Autocode

    public function getAttachmentUrl()
    {
        return Router::url('attachment_view', ['id' => $this->getAttachmentId()]);
    }

    public function isImage()
    {
        return isset($this->mimetype) && GSFile::mimetypeMajor($this->mimetype) == 'image';
    }

    /**
     * Get the HTML attributes for this attachment
     */
    public function getImageHTMLAttributes(array $orig = [], bool $overwrite = true)
    {
        if ($this->isImage()) {
            $attrs = [
                'height' => $this->getHeight(),
                'width'  => $this->getWidth(),
                'src'    => $this->getAttachmentUrl(),
            ];
            return $overwrite ? array_merge($orig, $attrs) : array_merge($attrs, $orig);
        } else {
            return false;
        }
    }

    public function getFilepath()
    {
        return Common::config('storage', 'dir') . $this->filename;
    }

    public static function schemaDef()
    {
        return [
            'name'   => 'attachment_embed',
            'fields' => [
                'attachment_id' => ['type' => 'int', 'not null' => true, 'description' => 'Embed for that URL/file'],
                'mimetype'      => ['type' => 'varchar', 'length' => 50, 'description' => 'mime type of resource'],
                'filename'      => ['type' => 'varchar', 'length' => 191, 'description' => 'file name of resource when available'],
                'provider'      => ['type' => 'text', 'description' => 'name of this oEmbed provider'],
                'provider_url'  => ['type' => 'text', 'description' => 'URL of this oEmbed provider'],
                'width'         => ['type' => 'int', 'description' => 'width of oEmbed resource when available'],
                'height'        => ['type' => 'int', 'description' => 'height of oEmbed resource when available'],
                'html'          => ['type' => 'text', 'description' => 'html representation of this Embed resource when applicable'],
                'title'         => ['type' => 'text', 'description' => 'title of Embed resource when available'],
                'author_name'   => ['type' => 'text', 'description' => 'author name for this Embed resource'],
                'author_url'    => ['type' => 'text', 'description' => 'author URL for this Embed resource'],
                'media_url'     => ['type' => 'text', 'description' => 'URL for this Embed resource when applicable (photo, link)'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['attachment_id'],
            'foreign keys' => [
                'attachment_embed_attachment_id_fkey' => ['attachment', ['attachment_id' => 'id']],
            ],
        ];
    }
}
