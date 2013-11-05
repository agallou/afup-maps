<?php

namespace Afup\Maps\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Generate extends Command
{
    protected function configure()
    {
        $this
            ->setName('generate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $rootDir = __DIR__ . '/../../../../';

        $loader = new \Twig_Loader_Filesystem($rootDir . 'views/');
        $twig = new \Twig_Environment($loader);

        $geoInfos = array();
        //latitude, //longitude //info
        $geoInfos[] = array("47.635784", "1.215819", "");

        echo $twig->render('base.twig', array(
          'geo' => $geoInfos,
        ));
    }
}
