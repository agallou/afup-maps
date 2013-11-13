<?php

namespace Afup\Maps\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFromBarometre extends Generate
{
    protected function configure()
    {
        $this
            ->setName('generate:from-barometre')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Ficher CSV exporté du baromètre'
            )
        ;
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
    protected function geoCodeLine(\Geocoder\Geocoder $geocoder, OutputInterface $output, array $line)
    {
        $code = $line[0];
        if (null == $code || "0" == $code) {
          return null;
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
        return $result;
    }


    /**
     * @return string
     */
    protected function getOutputFilename()
    {
        return 'index.html';
    }

    /**
     * @param \SplFileObject $file
     *
     * @return mixed|void
     */
    protected function initCsv(\SplFileObject $file)
    {
        $file->setCsvControl(',', "\"");
    }

    /**
     * @return bool
     */
    protected function skipFirstLine()
    {
        return true;
    }
}
