<?php
/**
 * @package SmartTrackerConsole
 * @author Keyser Söze
 * @copyright Copyright (c) 2014 Keyser Söze
 * Displays <a href="http://creativecommons.org/licenses/MIT/deed.fr">MIT</a>
 * @license http://creativecommons.org/licenses/MIT/deed.fr MIT
 */

function includeIfExists($file) {
    return file_exists($file) ? include $file : false;
}

if (
    (!$loader = includeIfExists(__DIR__ . '/../vendor/autoload.php')) &&
    (!$loader = includeIfExists(__DIR__ . '/../../../autoload.php'))
) {

    echo 'You must set up the project dependencies, run the following commands:' . PHP_EOL .
         'curl -sS https://getcomposer.org/installer | php' . PHP_EOL .
         'php composer.phar install' . PHP_EOL;
    exit(1);
}

return $loader;
