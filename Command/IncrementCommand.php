<?php

namespace Kachkaev\AssetsVersionBundle\Command;

use Kachkaev\AssetsVersionBundle\AssetsVersionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IncrementCommand extends Command
{
    private AssetsVersionManager $assetsVersionManager;
    private string $parameterName;
    private string $filePath;

    /**
     * IncrementCommand constructor.
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

    protected function configure(): void
    {
        $this
            ->setDescription('Increments assets version parameter')
            ->addArgument(
                    'delta',
                    InputArgument::OPTIONAL,
                    'New value for assets version',
                    1
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $output->writeln('Incrementing parameter <info>'.$this->parameterName.'</info> in <info>'.basename($this->filePath).'</info> by <info>'.var_export($input->getArgument('delta'), true).'</info>...');

        $assetsVersionUpdater = $this->assetsVersionManager;
        $assetsVersionUpdater->incrementVersion($input->getArgument('delta'));

        $output->writeln('Done. New value for <info>'.$this->parameterName.'</info> is <info>'.$assetsVersionUpdater->getVersion().'</info>. Clearing of <info>prod</info> cache is required.');

        self::SUCCESS;
    }
}
