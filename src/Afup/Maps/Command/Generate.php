<?php

namespace Afup\Maps\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Generate extends Command
{

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
        $this->initCsv($file);
        $first = true;
        $cpt = 0;
        $ignored = 0;
        foreach ($file as $line) {
          $cpt++;
          if ($first) {
            $first = false;
            if ($this->skipFirstLine()) {
               continue;
            }
          }
          if (!is_array($line)) {
              continue;
          }
          $result = $this->geoCodeLine($geocoder, $output, $line);
          if (null === $result) {
              $ignored++;
              $output->writeln(sprintf('Line %s ignorée ("%s")', $cpt, var_export($line, true)));
              continue;
          }

            if ($cpt == 120) {
                //die(var_dump($line));
            }

          $geoInfos[] = array($result['latitude'], $result['longitude'], $cpt);
        }

        $filename = $rootDir . 'output/' . $this->getOutputFilename();
        file_put_contents($filename, $twig->render('base.twig', array(
          'geo' => $geoInfos,
        )));
        $output->writeln(sprintf('<comment>%s lignes ignores</comment>', $ignored));

        $output->writeln(sprintf('File <info>%s</info> written', realpath($filename)));
    }

    /**
     * @param \Geocoder\Geocoder $geocoder
     * @param OutputInterface $output
     * @param array $line
     *
     * @return \Geocoder\Result\ResultInterface|\Geocoder\ResultInterface|null
     *
     * @throws \Exception
     */
    abstract protected function geoCodeLine(\Geocoder\Geocoder $geocoder, OutputInterface $output, array $line);

    /**
     * @return string
     */
    abstract protected function getOutputFilename();

    /**
     * @param \SplFileObject $file
     *
     * @return mixed
     */
    abstract protected function initCsv(\SplFileObject $file);

    /**
     * @return bool
     */
    protected function skipFirstLine()
    {
        return false;
    }

}
