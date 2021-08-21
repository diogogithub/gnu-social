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
    const MODULE_TYPE = 'plugin';

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
