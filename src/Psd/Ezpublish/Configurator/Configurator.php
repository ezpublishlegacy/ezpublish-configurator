<?php

namespace Psd\Ezpublish\Configurator;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Configurator {

    public function __construct()
    {
        $console = new Application();

        $console
            ->register('update-configuration')
            ->setDefinition(
                array(
                    new InputArgument('configuration-file', InputArgument::REQUIRED, 'The configuration file.'),
                    new InputArgument('ezpublish-root', InputArgument::REQUIRED, 'The eZ Publish root directory.')
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

        $file = $input->getArgument('configuration-file');
        $config = file_get_contents($file);
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
                    throw new \Exception(sprintf('File not found "%s", cannot overwrite block values in ini file.', $file));
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
            }
        }

        // Rechange to previous directory.
        chdir($current_dir);
    }

    protected function validateInput(InputInterface $input, OutputInterface $output)
    {
        $root_dir      = $input->getArgument('ezpublish-root');
        $autoload_file = $root_dir.'/'.'autoload.php';

        if (is_dir(realpath($root_dir)) === false) {
            $msg = sprintf('Could not find eZPublish root directory "%s".', realpath($root_dir));
            $output->writeln('<error>'.$msg.'</error>');
            exit(1);
        }

        if (is_file(realpath($autoload_file)) === false) {
            $msg = sprintf(
                'Could not find autoload.php file. Probably you do not point to a eZ Publish root directory.',
                $root_dir
            );
            $output->writeln('<error>'.$msg.'</error>');
            exit(1);
        }

        include $autoload_file;
    }

    protected function getFilename()
    {

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

