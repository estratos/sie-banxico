<?php

/**
 * PHPUnit bootstrap.
 *
 * Resolves the Composer autoloader for the current environment. When the
 * `SIE_BANXICO_COMPAT_VENDOR` environment variable points to another
 * installation (used to run this suite against several Symfony versions) its
 * autoloader is used instead of the repository one.
 *
 * When the test suite is executed from a Composer installation that does not
 * contain this bundle (the compatibility environments under `.compat/`), the
 * PSR-4 prefixes of the bundle and its own test namespace are registered on the
 * autoloader that was just loaded. PHPUnit's `--bootstrap` option makes this
 * file self-sufficient, so the tests can run from any invocation directory.
 */

declare(strict_types=1);

$vendorDir = getenv('SIE_BANXICO_COMPAT_VENDOR') ?: __DIR__ . '/../vendor';
$autoload = $vendorDir . '/autoload.php';

if (!is_file($autoload)) {
    fwrite(STDERR, sprintf('Composer autoloader not found at "%s". Run "composer install".%s', $autoload, PHP_EOL));
    exit(1);
}

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require $autoload;

$loader->addPsr4('Estratos\\SieBanxico\\Tests\\', __DIR__);
$loader->addPsr4('Estratos\\SieBanxico\\', \dirname(__DIR__) . '/src');
