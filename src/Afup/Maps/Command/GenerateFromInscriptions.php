<?php

namespace Afup\Maps\Command;

use Geocoder\Exception\NoResultException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFromInscriptions extends Generate
{
    protected function configure()
    {
        $this
            ->setName('generate:from-inscriptions')
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

        if ($line[2] == 'Futuroscope' || $line[2] == 'FUTUROSCOPE' || $line[2] == 'FUTUROSCOPE CEDEX') {
            $line[1] = '86360';
            $line[2] = 'Chasseneuil-du-Poitou';
        }

        if ($line[1] == '59666' && $line[2] == "VILLENEUVE D'ASCQ CEDEX") {
            $line[1] = "59650";
            $line[2] = "VILLENEUVE D'ASCQ";

        }

        if ($line[2] == 'Oujda') {
            $line[1] = '';
            $line[2] .= ' Maroc';
        }

        if ($line[2] == 'Marcq-en-Baroeul Cedex')
        {
            $line[1] = "59700";
        }

        if ($line[2] == 'Amabcourt') {
            $line[2] = "Ambacourt";
        }

        if ($line[0] == '66, Boulevard Niels Bohr') {
            $line[1] = '69622';
        }

        if ($line[2] == 'Antony Cedex') {
            $line[1] = '92160';
        }

        if ($line[2] == 'saint-c' && $line[1] == "83270") {
            $line[2] = 'Saint-Cyr-sur-Mer';
        }

        if ($line[2] == 'Luxembourg') {
            $line[1] = '';
        }


        $address = sprintf("%s %s", $line[1], $line[2]);

        $address = str_replace(array('cedex', 'Cedex 14', 'Cedex', 'CEDEX'), '', $address);

        try
        {
            $result = $geocoder->geocode($address);
        }
        catch (NoResultException $exception)
        {
            $output->writeln(sprintf('<error>%s</error>', $address));
            return null;
        }
        return $result;
    }

    /**
     * @return string
     */
    protected function getOutputFilename()
    {
        return 'inscriptions.html';
    }

    /**
     * @param \SplFileObject $file
     *
     * @return mixed|void
     */
    protected function initCsv(\SplFileObject $file)
    {
        $file->setCsvControl(';', "\"");
    }
}
