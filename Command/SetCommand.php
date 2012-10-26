<?php

namespace Kachkaev\AssetsVersionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class SetCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('assets_version:set')
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

    	$output->writeln('Setting parameter <info>'.$this->getContainer()->getParameter('kachkaev_assets_version.parametername').'</info> in <info>'.basename($this->getContainer()->getParameter('kachkaev_assets_version.filename')).'</info> to <info>'.$input->getArgument('value').'</info>...');
    	
    	$assetsVersionUpdater = $this->getContainer()->get('kachkaev_assets_version.assets_version_manager');
    	$assetsVersionUpdater->setVersion($input->getArgument('value'));

    	$output->writeln('Done. Clearing of <info>prod</info> cache is required.');
    }
}