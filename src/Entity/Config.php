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

/**
 * Entity for app configuration
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
class Config
{
    // {{{ Autocode

    private string $section = '';
    private string $setting = '';
    private ?string $value;

    public function setSection(string $section): self
    {
        $this->section = $section;
        return $this;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function setSetting(string $setting): self
    {
        $this->setting = $setting;
        return $this;
    }

    public function getSetting(): string
    {
        return $this->setting;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'config',
            'fields' => [
                'section' => ['type' => 'varchar', 'length' => 32, 'not null' => true, 'default' => '', 'description' => 'configuration section'],
                'setting' => ['type' => 'varchar', 'length' => 32, 'not null' => true, 'default' => '', 'description' => 'configuration setting'],
                'value'   => ['type' => 'text', 'description' => 'configuration value'],
            ],
            'primary key' => ['section', 'setting'],
        ];
    }
}
