<?php

declare(strict_types=1);

namespace MultipleAuthors\Mcp;

\defined('ABSPATH') || exit;

/** Register domain abilities independently of Hub and request identity. @since 4.18.0 */
class Bootstrap
{
    public static function register()
    {
        Profiles::register();
        Settings::register();
        Bylines::register();
        Objects::register();
        Lists::register();
        Categories::register();
    }
}
