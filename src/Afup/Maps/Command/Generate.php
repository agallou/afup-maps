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
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Ficher CSV exporté du baromètre'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $rootDir = __DIR__ . '/../../../../';

        $loader = new \Twig_Loader_Filesystem($rootDir . 'views/');
        $twig = new \Twig_Environment($loader);


        $cachePlugin = new \Guzzle\Plugin\Cache\CachePlugin(array(
          'storage' => new \Guzzle\Plugin\Cache\DefaultCacheStorage(
            new \Guzzle\Cache\DoctrineCacheAdapter(
              new \Doctrine\Common\Cache\FilesystemCache($rootDir . '/cache')
            )
          )
        ));

        $client = new \Guzzle\Service\Client();
        $client->addSubscriber($cachePlugin);
        $adapter =  new \Geocoder\HttpAdapter\GuzzleHttpAdapter($client);
        $provider =  new \Geocoder\Provider\OpenStreetMapProvider($adapter, 'fr_FR', 'France', true);

        $geocoder = new \Geocoder\Geocoder();
        $geocoder->registerProvider($provider);


        $geoInfos = array();

        $file = new \SplFileObject($input->getArgument('file'));
        $file->setFlags(\SplFileObject:: READ_CSV + \SplFileObject::DROP_NEW_LINE + \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(',', "\"");
        $first = true;
        $cpt = 0;
        $ignored = 0;
        foreach ($file as $line) {
          $cpt++;
          if ($first) {
            $first = false;
            continue;
          }
          $code = $line[0];
          if (null == $code || "0" == $code) {
            $ignored++;
            $output->writeln(sprintf('Line %s ignorée ("%s")', $cpt, $line[0]));
            continue;
          }
          if (strlen($code) == 2) {
             $code .= '000';
             $result = $geocoder->geocode($code . ' France');
             if ($result['zipcode'] != $code) {
                throw new \Exception("Région incohérente");
             }
          } else {
            $result = $geocoder->geocode($code);
          }
          $geoInfos[] = array($result['latitude'], $result['longitude'], $cpt);
        }

        $filename = $rootDir . 'output/index.html';
        file_put_contents($filename, $twig->render('base.twig', array(
          'geo' => $geoInfos,
        )));
        $output->writeln(sprintf('<comment>%s lignes ignores</comment>', $ignored));

        $output->writeln(sprintf('File <info>%s</info> written', realpath($filename)));
    }
}
