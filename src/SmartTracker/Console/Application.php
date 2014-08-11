<?php
/**
 * @package SmartTrackerConsole
 * @author Keyser Söze
 * @copyright Copyright (c) 2014 Keyser Söze
 * Displays <a href="http://creativecommons.org/licenses/MIT/deed.fr">MIT</a>
 * @license http://creativecommons.org/licenses/MIT/deed.fr MIT
 */

/**
* @namespace
*/
namespace SmartTracker\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use SmartTracker\SmartTrackerConsole;
use SmartTracker\Util\ErrorHandler;

class Application extends BaseApplication
{

    protected $config;

    private static $logo = '   _____                      __     ______                __
  / ___/____ ___  ____ ______/ /_   /_  __/________ ______/ /_____  _____
  \__ \/ __ `__ \/ __ `/ ___/ __/    / / / ___/ __ `/ ___/ //_/ _ \/ ___/
 ___/ / / / / / / /_/ / /  / /_     / / / /  / /_/ / /__/ ,< /  __/ /
/____/_/ /_/ /_/\__,_/_/   \__/    /_/ /_/   \__,_/\___/_/|_|\___/_/

';

    public function __construct()
    {
        if (function_exists('ini_set') && extension_loaded('xdebug')) {
            ini_set('xdebug.show_exception_trace', false);
            ini_set('xdebug.scream', false);
        }

        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(@date_default_timezone_get());
        }

        ErrorHandler::register();
        parent::__construct('SmartTracker', SmartTrackerConsole::VERSION);
    }

    /**
     * {@inheritDoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $output) {
            $formatter = new OutputFormatter(null, array(
                'highlight' => new OutputFormatterStyle('red'),
                'warning'   => new OutputFormatterStyle('black', 'yellow'),
            ));
            $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_NORMAL, null, $formatter);
        }

        return parent::run($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $name = $this->getCommandName($input);

        if (
            !in_array($name, array("help", "config")) &&
            !file_exists($_SERVER['HOME'] . '/.smarttracker')
        ) {
            $output->writeln("<error>Please run ./smarttracker config</error>");
            return 1;
        }

        if ($input->hasOption("config")) {
            $this->config = $input->getOption("config");
        }

        if (empty($this->config)) {
            $this->config = $_SERVER['HOME'] . '/.smarttracker';
        }

        return parent::doRun($input, $output);
    }

    public function getHelp()
    {
        return self::$logo . parent::getHelp();
    }

    /**
     * Initializes all the composer commands
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new Command\UploadCommand();
        $commands[] = new Command\ConfigCommand();

        return $commands;
    }

    /**
     * {@inheritDoc}
     */
    public function getLongVersion()
    {
        return parent::getLongVersion() . ' ' . SmartTrackerConsole::RELEASE_DATE;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption('config', null, InputOption::VALUE_OPTIONAL, 'TThe config file'));

        return $definition;
    }

    public function getConfigFile()
    {
        return $this->config;
    }
}
