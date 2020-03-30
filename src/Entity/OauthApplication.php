<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as publ
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public Li
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

namespace App\Entity;

/**
 * Entity for OAuth Application
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OauthApplication
{
    // {{{ Autocode

    private int $id;
    private int $owner;
    private string $consumer_key;
    private string $name;
    private ?string $description;
    private string $icon;
    private ?string $source_url;
    private ?string $organization;
    private ?string $homepage;
    private ?string $callback_url;
    private ?int $type;
    private ?int $access_type;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getId(): int
    {
        return $this->id;
    }

    public function setOwner(int $owner): self
    {
        $this->owner = $owner;
        return $this;
    }
    public function getOwner(): int
    {
        return $this->owner;
    }

    public function setConsumerKey(string $consumer_key): self
    {
        $this->consumer_key = $consumer_key;
        return $this;
    }
    public function getConsumerKey(): string
    {
        return $this->consumer_key;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }
    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setSourceUrl(?string $source_url): self
    {
        $this->source_url = $source_url;
        return $this;
    }
    public function getSourceUrl(): ?string
    {
        return $this->source_url;
    }

    public function setOrganization(?string $organization): self
    {
        $this->organization = $organization;
        return $this;
    }
    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setHomepage(?string $homepage): self
    {
        $this->homepage = $homepage;
        return $this;
    }
    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setCallbackUrl(?string $callback_url): self
    {
        $this->callback_url = $callback_url;
        return $this;
    }
    public function getCallbackUrl(): ?string
    {
        return $this->callback_url;
    }

    public function setType(?int $type): self
    {
        $this->type = $type;
        return $this;
    }
    public function getType(): ?int
    {
        return $this->type;
    }

    public function setAccessType(?int $access_type): self
    {
        $this->access_type = $access_type;
        return $this;
    }
    public function getAccessType(): ?int
    {
        return $this->access_type;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }
    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    public function getModified(): \DateTimeInterface
    {
        return $this->modified;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'oauth_application',
            'description' => 'OAuth application registration record',
            'fields'      => [
                'id'           => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'owner'        => ['type' => 'int', 'not null' => true, 'description' => 'owner of the application'],
                'consumer_key' => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'application consumer key'],
                'name'         => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'name of the application'],
                'description'  => ['type' => 'varchar', 'length' => 191, 'description' => 'description of the application'],
                'icon'         => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'default' => '/theme/base/default-avatar-stream.png', 'description' => 'application icon'],
                'source_url'   => ['type' => 'varchar', 'length' => 191, 'description' => 'application homepage - used for source link'],
                'organization' => ['type' => 'varchar', 'length' => 191, 'description' => 'name of the organization running the application'],
                'homepage'     => ['type' => 'varchar', 'length' => 191, 'description' => 'homepage for the organization'],
                'callback_url' => ['type' => 'varchar', 'length' => 191, 'description' => 'url to redirect to after authentication'],
                'type'         => ['type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'type of app, 1 = browser, 2 = desktop'],
                'access_type'  => ['type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'default access type, bit 1 = read, bit 2 = write'],
                'created'      => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'     => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'oauth_application_name_key' => ['name'], // in the long run, we should perhaps not force these unique, and use another source id
            ],
            'foreign keys' => [
                'oauth_application_owner_fkey'        => ['profile', ['owner' => 'id']], // Are remote users allowed to create oauth application records?
                'oauth_application_consumer_key_fkey' => ['consumer', ['consumer_key' => 'consumer_key']],
            ],
        ];
    }
}