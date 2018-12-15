<?php

namespace Kachkaev\AssetsVersionBundle\Command;

use Kachkaev\AssetsVersionBundle\AssetsVersionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends Command
{
    private $assetsVersionManager;
    private $parameterName;
    private $filePath;

    /**
     * SetCommand constructor.
     *
     * @param AssetsVersionManager $assetsVersionManager
     * @param string               $parameterName
     * @param string               $filePath
     */
    public function __construct(AssetsVersionManager $assetsVersionManager, string $parameterName, string $filePath)
    {
        parent::__construct();
        $this->assetsVersionManager = $assetsVersionManager;
        $this->parameterName = $parameterName;
        $this->filePath = $filePath;
    }

    protected function configure()
    {
        $this
            ->setDescription('Sets assets version parameter to a given value')
            ->addArgument(
                    'value',
                    InputArgument::REQUIRED,
                    'New value for assets version'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Setting parameter <info>'.$this->parameterName.'</info> in <info>'.basename($this->filePath).'</info> to <info>'.var_export($input->getArgument('value'), true).'</info>...');

        $assetsVersionUpdater = $this->assetsVersionManager;
        $assetsVersionUpdater->setVersion($input->getArgument('value'));

        $output->writeln('Done. Clearing of <info>prod</info> cache is required.');
    }
}
