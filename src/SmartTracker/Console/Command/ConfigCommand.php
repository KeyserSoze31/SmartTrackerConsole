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
namespace SmartTracker\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('config')
            ->setDescription('Config SmartTracker console')
            ->setDefinition(array(
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite config file.')
            ));;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configfile = $this->getApplication()->getConfigFile();

        if (!$input->hasOption("force") && file_exists($configfile)) {
            $output->writeln(sprintf("<error>The config file %s exists.</error>", $configfile));
            return 1;
        }

        $dialog = $this->getHelperSet()->get('dialog');

        $provider = $dialog->ask(
            $output,
            'The provider url: ',
            ''
        );

        if (!filter_var($provider, FILTER_VALIDATE_URL)) {
            $output->writeln("<error>The provider is not a valid URL.</error>");
            return 1;
        }

        $parts = parse_url($provider);

        $provider = sprintf("%s://%s", $parts["scheme"], $parts["host"]);

        $key = $dialog->ask(
            $output,
            'The API key: ',
            ''
        );

        if (!preg_match("/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/", $key)) {
            $output->writeln("<error>The api key is not valid.</error>");
            return 1;
        }

        $config = sprintf("provider = %s\nkey = %s\n", $provider, $key);

        if (file_put_contents($configfile, $config) === false) {
            $output->writeln("<error>the .smarttracker file could not be created/</error>");
            return 1;
        }

        return 0;
    }
}
