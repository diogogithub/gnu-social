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

namespace App\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Event;
use App\Core\GSFile;
use App\Core\HTTPClient;
use App\Util\Common;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NotFoundException;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Entity for representing a RemoteURL
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RemoteURL extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private ?string $remote_url;
    private ?string $remote_url_hash;
    private ?string $mimetype;
    private DateTimeInterface $modified;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRemoteUrl(): ?string
    {
        return $this->remote_url;
    }

    public function setRemoteUrl(?string $remote_url): self
    {
        $this->remote_url = $remote_url;
        return $this;
    }

    public function setRemoteUrlHash(?string $remote_url_hash): self
    {
        $this->remote_url_hash = $remote_url_hash;
        return $this;
    }

    public function getRemoteUrlHash(): ?string
    {
        return $this->remote_url_hash;
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

    public function getMimetypeMajor(): ?string
    {
        $mime = $this->getMimetype();
        return is_null($mime) ? $mime : GSFile::mimetypeMajor($mime);
    }

    public function getMimetypeMinor(): ?string
    {
        $mime = $this->getMimetype();
        return is_null($mime) ? $mime : GSFile::mimetypeMinor($mime);
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

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    const URLHASH_ALGO = 'sha256';

    /**
     * Create an attachment for the given URL, fetching the mimetype
     *
     * @param string $url
     *
     * @throws DuplicateFoundException
     * @throws InvalidArgumentException
     *
     * @return RemoteURL
     */
    public static function getOrCreate(string $url): self
    {
        if (Common::isValidHttpUrl($url)) {
            $head = HTTPClient::head($url);
            // This must come before getInfo given that Symfony HTTPClient is lazy (thus forcing curl exec)
            $headers  = $head->getHeaders();
            $url      = $head->getInfo('url'); // The last effective url (after getHeaders, so it follows redirects)
            $url_hash = hash(self::URLHASH_ALGO, $url);
            try {
                return DB::findOneBy('remoteurl', ['remote_url_hash' => $url_hash]);
            } catch (NotFoundException) {
                $headers   = array_change_key_case($headers, CASE_LOWER);
                $remoteurl = self::create([
                    'remote_url'      => $url,
                    'remote_url_hash' => $url_hash,
                    'mimetype'        => $headers['content-type'][0],
                ]);
                DB::persist($remoteurl);
                Event::handle('RemoteURLStoredNew', [&$remoteurl]);
                return $remoteurl;
            }
        } else {
            throw new InvalidArgumentException();
        }
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'remoteurl',
            'fields' => [
                'id'              => ['type' => 'serial',    'not null' => true],
                'remote_url'      => ['type' => 'text',      'description' => 'URL after following possible redirections'],
                'remote_url_hash' => ['type' => 'varchar',   'length' => 64,  'description' => 'sha256 of destination URL (url field)'],
                'mimetype'        => ['type' => 'varchar',   'length' => 50,  'description' => 'mime type of resource'],
                'modified'        => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'indexes'     => [
                'gsactor_remote_url_hash_idx' => ['remote_url_hash'],
            ],
        ];
    }
}
