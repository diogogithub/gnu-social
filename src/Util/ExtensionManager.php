<?php

namespace App\Util;

use App\Util\GSEvent as Event;
use Functional as F;

abstract class ExtensionManager
{
    public static array $extensions = [];

    public static function loadExtensions()
    {
        $plugins_paths = glob(INSTALLDIR . '/plugins/enabled/*');

        foreach ($plugins_paths as $plugin_path) {
            $class_name = basename($plugin_path);
            $qualified  = 'Plugin\\' . $class_name . '\\' . $class_name;

            require_once $plugin_path . '/' . $class_name . '.php';
            $class              = new $qualified;
            self::$extensions[] = $class;

            // Register event handlers
            $methods = get_class_methods($class);
            $events  = F\select($methods, Common::swapArgs('startsWith', 'on'));
            F\map($events,
                  function (string $m) use ($class) {
                      Event::addHandler(substr($m, 2), [$class, $m]);
                  });
        }
    }
}
