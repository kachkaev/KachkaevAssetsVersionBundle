<?php

namespace Kachkaev\AssetsVersionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('assets-version:set')
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

        $output->writeln('Setting parameter <info>'.$this->getContainer()->getParameter('kachkaev_assets_version.parameter_name').'</info> in <info>'.basename($this->getContainer()->getParameter('kachkaev_assets_version.file_path')).'</info> to <info>'.var_export($input->getArgument('value'), true).'</info>...');

        $assetsVersionUpdater = $this->getContainer()->get('kachkaev_assets_version.assets_version_manager');
        $assetsVersionUpdater->setVersion($input->getArgument('value'));

        $output->writeln('Done. Clearing of <info>prod</info> cache is required.');
    }
}
