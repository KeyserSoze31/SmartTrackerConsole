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

use SplFileInfo;
use RuntimeException;
use InvalidArgumentException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use SmartTracker\Util\TorrentType;

class UploadCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName("upload")
            ->setDescription("Upload torrent on server")
            ->setDefinition(array(
                new InputArgument("torrent", InputArgument::REQUIRED, "The torrent file."),
                new InputArgument("nfo", InputArgument::OPTIONAL, "The NFO file."),
                new InputOption("type", "t", InputOption::VALUE_OPTIONAL, "Type of torrent: tvshow, movie or music"),
                new InputOption("output-dir", "o", InputOption::VALUE_OPTIONAL, "The path of output directory for downloaded torrent.")
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!extension_loaded("curl")) {
            throw new RuntimeException("Missing ext/curl");
        }

        $output_path = rtrim($input->getOption("output-dir"), "/");

        if (empty($output_path)) {
            $output_path = "./";
        }

        if (!is_dir($output_path)) {
            throw new InvalidArgumentException(sprintf(
                "The output path %s is not a directory.",
                $output_path
            ));
        }

        $config = parse_ini_file($this->getApplication()->getConfigFile());

        $torrent = new SplFileInfo($input->getArgument("torrent"));

        $output->writeln(sprintf("Torrent: <info>%s</info>", $torrent->getBasename()));

        $nfo = null;

        if ($input->hasArgument("nfo")) {
            $nfo = new SplFileInfo($input->getArgument("nfo"));

            $output->writeln(sprintf("NFO: <info>%s</info>", $nfo->getBasename()));
        }

        if (!$torrent->isFile()) {
            throw new InvalidArgumentException(sprintf(
                "%s is not file.",
                $torrent->getRealPath()
            ));
        }

        if (!$torrent->isReadable()) {
            throw new InvalidArgumentException(sprintf(
                "Cannot read file %s.",
                $torrent->getRealPath()
            ));
        }

        if (!empty($nfo)) {
            if (!$nfo->isFile()) {
                throw new InvalidArgumentException(sprintf(
                    "%s is not file.",
                    $torrent->getRealPath()
                ));
            }

            if (!$nfo->isReadable()) {
                throw new InvalidArgumentException(sprintf(
                    "Cannot read file %s.",
                    $torrent->getRealPath()
                ));
            }
        }

        $type = $input->getOption("type");

        if (empty($type)) {
            $type = TorrentType::parseType($torrent->getFilename());
        } else {
            $type = TorrentType::getType($type);
        }

        $output->writeln(sprintf("Type: <info>%s</info>", TorrentType::getTypeName($type)));



        // start http client
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config["provider"] . '/upload?key=' . $config["key"]);

        $data = array(
            'torrent'   => curl_file_create(
                $torrent->getRealPath(),
                'application/x-bittorrent',
                $torrent->getBasename('.torrent')
            ),
            'type'      => $type
        );

        if (!empty($nfo))
        {
            $data["nfo"] = curl_file_create(
                $nfo->getRealPath(),
                'text/plain',
                $nfo->getBasename('.nfo')
            );
        }

        $output->writeln("Start upload");

        // Assign POST data
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $progress = $this->getHelperSet()->get("progress");

        $progress->start($output, 0);

        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($clientp, $download_size, $downloaded, $upload_size, $uploaded) use ($progress) {
            $progress->setCurrent(($uploaded / $upload_size * 100));
        });

        $progress->finish();

        $response = curl_exec($ch);

        if (!empty($response)) {
            $response = json_decode($response);
        }

        //echo $response . PHP_EOL;

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 201) {
            $error = curl_error($ch);
            curl_close($ch);

            if (!empty($response)) {
                $error = $response->error->message;
            }

            throw new RuntimeException($error);
        }

        curl_close($ch);

        $torrent_name = $torrent->getBasename('.torrent') . '.torrent';

        $output->writeln("Start download");

        // download torrent
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf($config["provider"] . "/torrent/%d", $response->id) . '?key=' . $config["key"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) use (&$torrent_name) {

            if (strpos($header, "Content-Disposition:") !== false) {
                $torrent_name = substr($header, 43, strlen($header) - 46);
            }

            return strlen($header);
        });

        $progress->start($output, 0);

        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($clientp, $download_size, $downloaded, $upload_size, $uploaded) use ($progress) {
            $progress->setCurrent(($downloaded / $download_size * 100));
        });

        $progress->finish();

        $data = curl_exec($ch);
        curl_close($ch);

        if (file_put_contents($output_path . '/' .  $torrent_name, $data) !== false) {
            $output->writeln(sprintf("Downloaded torrent: <info>%s</info>", $output_path . '/' .  $torrent_name));
        } else {
            $output->writeln(sprintf("<error>Unable to write to the file: %s</error>", $output_path . '/' .  $torrent_name));
        }
    }
}
