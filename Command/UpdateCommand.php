<?php

namespace Kachkaev\AssetsVersionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class UpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('assets_version:update')
            ->setDescription('Increments assets version parameter if were changes in files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $assetsVersionUpdater = $this->getContainer()->get('kachkaev_assets_version.assets_version_manager');
        $fileList = $assetsVersionUpdater->getDefinedFilesList();
        if (!$fileList) {

            return  $output->writeln('There is empty parameter <info>kachkaev_assets_version.scannedfiles</info>');
        }

        $output->writeln('Checking updates in files: <info>' . $fileList . '</info>');

        if ($assetsVersionUpdater->updateHash()) {
            $output->writeln('Done. New value for <info>'.$this->getContainer()->getParameter('kachkaev_assets_version.parametername').'</info> is <info>'.$assetsVersionUpdater->getVersion().'</info>. Clearing of <info>prod</info> cache is required.');
        } else {
            $output->writeln('There changes in the files were not found.');
        }
    }
}