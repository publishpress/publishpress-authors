<?php
/** Standalone missing-Abilities-API compatibility contract. */
define('ABSPATH', __DIR__);
function add_action() { throw new RuntimeException('Missing API must not register integration hooks.'); }
require __DIR__ . '/../../src/abilities.php';
echo "Missing-API compatibility passed.\n";
