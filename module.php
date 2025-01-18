<?php

/**
 * See LICENSE.md file for further details.
 */

declare(strict_types=1);

namespace LilaElephant\Webtrees;

use Composer\Autoload\ClassLoader;
use LilaElephant\Webtrees\Topola\Module;
use Fisharebest\Webtrees\Registry;

// Register our namespace
$loader = new ClassLoader();
$loader->addPsr4(
    'LilaElephant\\Webtrees\\Topola\\',
    __DIR__ . '/src'
);
$loader->register();

// Create and return instance of the module
return Registry::container()->get(Module::class);
