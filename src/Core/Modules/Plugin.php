<?php

namespace App\Core\Modules;

use App\Core\Event;

/**
 * TODO Plugins aren't tested yet
 *
 * @codeCoverageIgnore
 */
abstract class Plugin extends Module
{
    public function __construct()
    {
        parent::__construct();
    }

    public function name()
    {
        return mb_substr(static::class, 0, -6);
    }

    public function version()
    {
        return GNUSOCIAL_BASE_VERSION;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $name = $this->name();

        $versions[] = [
            'name' => $name,
            // TRANS: Displayed as version information for a plugin if no version information was found.
            'version' => _m('Unknown'),
        ];

        return Event::next;
    }
}
