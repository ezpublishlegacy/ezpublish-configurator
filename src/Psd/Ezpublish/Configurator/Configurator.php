<?php

namespace Psd\Ezpublish\Configurator;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Configurator
{

    /**
     * @var Logger
     */
    private $logger = null;

    public function __construct()
    {
        $console = new Application();

        $console
            ->register('update-configuration')
            ->setDefinition(
                array(
                    new InputArgument('configuration-file', InputArgument::REQUIRED, 'The configuration file.'),
                    new InputArgument('ezpublish-root', InputArgument::REQUIRED, 'The eZ Publish root directory.'),
                    new InputOption(
                        'log-file',
                        null,
                        InputOption::VALUE_REQUIRED,
                        'A stream to log to. Default STDOUT',
                        'php://stdout'
                    )
                )
            )
            ->setDescription('Updates eZ publish configuration.')
            ->setHelp($this->getHelp())
            ->setCode(array($this, 'execute'));
        $console->run();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateInput($input, $output);

        $root_dir = $input->getArgument('ezpublish-root');

        $ymlFile = $input->getArgument('configuration-file');
        $config = file_get_contents($ymlFile);
        $directories = Yaml::parse($config);

        // Change dir to ezpublish root.
        // Dirty hack for eZ.
        $current_dir = getcwd();
        chdir($root_dir);

        // First rewrite all INI files that should be overwritten.
        foreach ($directories as $directory => $fileConfig) {
            foreach ($fileConfig as $filename => $config) {
                // Check if files are available.
                $file = sprintf('%s/%s', $directory, $filename);
                if (file_exists($file) === false) {
                    $this->logger->log(
                        Logger::ERROR,
                        sprintf('File not found "%s", cannot overwrite block values in ini file.', $file)
                    );
                    continue;
                }

                $ini = \eZINI::fetchFromFile($file);

                $blockValueKeys = array_keys($config);

                foreach ($blockValueKeys as $block) {

                    if (isset($ini->BlockValues[$block]) === true) {
                        $blockValues = array_merge($ini->BlockValues[$block], $config[$block]);
                    } else {
                        $blockValues = $config[$block];
                    }
                    $ini->BlockValues[$block] = $blockValues;
                }
                $ini->save(realpath($file), false, false, false, true, false, true);
                $this->logger->log(
                    Logger::INFO,
                    "Updated ezpublish INI \"{$file}\" with settings from {$ymlFile}"
                );
            }
        }

        // Rechange to previous directory.
        chdir($current_dir);
    }

    protected function validateInput(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new Logger(
            'logger',
            array(new StreamHandler($input->getOption('log-file')))
        );

        $root_dir      = $input->getArgument('ezpublish-root');
        $configFile    = $input->getArgument('configuration-file');
        $autoload_file = $root_dir.'/'.'autoload.php';

        if (realpath($configFile) === false) {
            $msg = sprintf('Could not find configuration file "%s".', $configFile);
            $this->logger->log(Logger::EMERGENCY, $msg);
            exit(1);
        }

        if (is_dir(realpath($root_dir)) === false) {
            $msg = sprintf('Could not find eZPublish root directory "%s".', realpath($root_dir));
            $this->logger->log(Logger::EMERGENCY, $msg);
            exit(1);
        }

        if (is_file(realpath($autoload_file)) === false) {
            $msg = sprintf(
                'Could not find autoload.php file. Probably you do not point to a eZ Publish root directory.',
                $root_dir
            );
            $this->logger->log(Logger::EMERGENCY, $msg);
            exit(1);
        }

        include $autoload_file;
    }

    protected function getErrorText($prolog)
    {
        $error = <<<EOF

${prolog}

<error>{$this->getHelp()}</error>
EOF;

        return $error;
    }

    protected function getHelp()
    {
        $help = <<<EOF

# Sample configuration file for eZPublish 4.
# The settings file to override
settings/override:
    site.ini.append.php:
        # Defines block values that can be overwritten.
        DatabaseSettings:
            # The variable that can be overwritten.
            Server: 127.0.0.1
            Port: 3306
            User: dbuser
            Password: dbpassword
            Database: ez_db
            Charset: utf8
            Socket: /var/lib/mysql/mysql.sock
        SiteSettings:
            SiteName: Site Name
            SiteURL: localhost
extension/mydesign/settings:
    site.ini:
        DatabaseSettings:
            # The variable that can be overwritten.
            Server: 127.0.0.1
            Port: 3306
            User: dbuser
            Password: dbpassword

EOF;
        return $help;
    }
}
