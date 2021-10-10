<?php

declare(strict_types = 1);

namespace App\Core\Modules;

use App\Core\Event;
use function App\Core\I18n\_m;

/**
 * TODO Plugins aren't tested yet
 *
 * @codeCoverageIgnore
 */
abstract class Plugin extends Module
{
    public const MODULE_TYPE = 'plugin';

    public function version(): string
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
