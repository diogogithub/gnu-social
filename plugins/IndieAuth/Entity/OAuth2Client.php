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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @category  OAuth2
 *
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\IndieAuth\Entity;

use App\Core\Entity;
use DateTimeInterface;

/**
 * OAuth application registration record
 *
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OAuth2Client extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private string $identifier;
    private ?string $secret;
    private string $redirect_uris       = '';
    private string $grants              = '';
    private string $scopes              = '';
    private bool $active                = true;
    private bool $allow_plain_text_pkce = false;
    private ?string $client_name        = null;
    private ?string $website            = null;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function __toString(): string
    {
        return $this->getIdentifier();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(?string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function getRedirectUris(): array
    {
        return explode(' ', $this->redirect_uris);
    }

    public function setRedirectUris(string ...$redirect_uris): self
    {
        $this->redirect_uris = implode(' ', $redirect_uris);

        return $this;
    }

    public function getGrants(): array
    {
        return explode(' ', $this->grants);
    }

    public function setGrants(string ...$grants): self
    {
        $this->grants = implode(' ', $grants);

        return $this;
    }

    public function getScopes(): array
    {
        return explode(' ', $this->scopes);
    }

    public function setScopes(string ...$scopes): self
    {
        $this->scopes = implode(' ', $scopes);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function isConfidential(): bool
    {
        return !empty($this->secret);
    }

    public function isPlainTextPkceAllowed(): bool
    {
        return $this->allow_plain_text_pkce;
    }

    public function setAllowPlainTextPkce(bool $allow_plain_text_pkce): self
    {
        $this->allow_plain_text_pkce = $allow_plain_text_pkce;

        return $this;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getClientName(): string
    {
        return $this->client_name;
    }

    public function setClientName(string $client_name): self
    {
        $this->client_name = $client_name;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;
        return $this;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
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

    /**
     * Return table definition for Schema setup and Entity usage.
     *
     * @return array array of column definitions
     */
    public static function schemaDef(): array
    {
        return [
            'name'   => 'oauth2_client',
            'fields' => [
                'identifier'            => ['type' => 'varchar', 'length' => 32, 'not null' => true, 'description' => 'foreign key to oauth2_client->identifier'],
                'secret'                => ['type' => 'varchar', 'length' => 128, 'not null' => false,  'description' => 'foreign key to oauth2_client->identifier'],
                'client_name'           => ['type' => 'varchar', 'length' => 191, 'not null' => false, 'description' => 'name of the application'],
                'redirect_uris'         => ['type' => 'text', 'not null' => false, 'description' => 'application homepage - used for source link'],
                'grants'                => ['type' => 'text', 'not null' => true, 'default' => '', 'description' => 'application homepage - used for source link'],
                'scopes'                => ['type' => 'text', 'not null' => true, 'default' => '', 'description' => 'application homepage - used for source link'],
                'active'                => ['type' => 'bool',      'not null' => true, 'description' => 'was this note generated by a local actor'],
                'allow_plain_text_pkce' => ['type' => 'bool',      'not null' => true, 'default' => false, 'description' => 'was this note generated by a local actor'],
                'website'               => ['type' => 'text', 'not null' => false, 'description' => 'application homepage - used for source link'],
                'created'               => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'              => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['identifier'],
        ];
    }
}
