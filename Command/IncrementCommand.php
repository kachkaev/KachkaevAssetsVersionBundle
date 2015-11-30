<?php

namespace Kachkaev\AssetsVersionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IncrementCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('assets-version:increment')
            ->setDescription('Increments assets version parameter')
            ->addArgument(
                    'delta',
                    InputArgument::OPTIONAL,
                    'New value for assets version',
                    1
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Incrementing parameter <info>'.$this->getContainer()->getParameter('kachkaev_assets_version.parameter_name').'</info> in <info>'.basename($this->getContainer()->getParameter('kachkaev_assets_version.file_path')).'</info> by <info>'.var_export($input->getArgument('delta'), true).'</info>...');

        $assetsVersionUpdater = $this->getContainer()->get('kachkaev_assets_version.assets_version_manager');
        $assetsVersionUpdater->incrementVersion($input->getArgument('delta'));

        $output->writeln('Done. New value for <info>'.$this->getContainer()->getParameter('kachkaev_assets_version.parameter_name').'</info> is <info>'.$assetsVersionUpdater->getVersion().'</info>. Clearing of <info>prod</info> cache is required.');
    }
}