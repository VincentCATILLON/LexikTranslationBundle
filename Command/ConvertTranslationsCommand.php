<?php

namespace Lexik\Bundle\TranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;

/**
 * Translations file convert command
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
class ConvertTranslationsCommand extends ContainerAwareCommand
{
    /**
     * Options values
     *
     * @var array $values
     */
    protected $values = array();

    /**
     * Translations values
     *
     * @var array $translations
     */
    protected $translations = array();

    /**
     * Translations counter
     *
     * @var array $count
     */
    protected $count = array();

    /**
     * Output interface
     *
     * @var OutputInterface $output
     */
    protected $output = array();

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('lexik:translations:convert')
            ->setDescription('Translation convert command from an input format to another one')
            ->setHelp('You must specify a file using the --file option.')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Specify a file to convert')
            ->addOption('input', null, InputOption::VALUE_REQUIRED, 'Specifiy an input translation format')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Specifiy an output translation format')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Specify a locale');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = array('file', 'input', 'output', 'locale');

        foreach ($options as $option) {
            $optionValue = $input->getOption($option);
            if (!$optionValue) {
                throw new \InvalidArgumentException('You must specify a --'.$option.' option.');
            }
            $this->values[$option] = $optionValue;
        }

        $this->output = $output;

        $this->loadTranslations();
        $this->mergeTranslations();
    }

    /**
     * New translations loader as array
     *
     * @throws FileNotFoundException, IOException
     */
    protected function loadTranslations()
    {
        $finder = new Finder();
        $finder->files()
            ->name(pathinfo($this->values['file'], PATHINFO_BASENAME))
            ->in(pathinfo($this->values['file'], PATHINFO_DIRNAME));

        $nbFiles = count($finder);
        if (!$nbFiles) {
            throw new FileNotFoundException(sprintf('File not found with name/regexp : "%s"', $this->values['file']));
        }

        $this->output->writeln(sprintf('<info>*** %s file(s) found ***</info>', $nbFiles));
        foreach ($finder as $file) {
            switch ($this->values['input']) {
                case 'csv':
                    $this->loadCsvFile($file);
                    break;
                default:
                    throw new IOException(sprintf('Input format not supported : "%s"', strtoupper($this->values['input'])));
            }
        }
    }

    /**
     * Translations merger
     */
    protected function mergeTranslations()
    {
        foreach ($this->translations as $path => $domains) {
            $this->output->writeln(sprintf('<info>Path "%s" ...</info>', $path));
            foreach ($domains as $domain => $translations) {
                $file = $this->getTranslationsFileName($path, $domain);
                $translations = array_replace_recursive($this->getYamlTranslations($file), $translations);
                $this->writeTranslations($path, $domain, $translations);
            }
        }
    }

    /**
     * Translations writer
     *
     * @param string $path
     * @param string $domain
     * @param array $translations
     * @return boolean
     */
    protected function writeTranslations($path, $domain, $translations)
    {
        $file = $this->getTranslationsFileName($path, $domain);
        $number = $this->count[$path][$domain];

        $this->output->writeln(sprintf('<comment>Writing file "%s" with <info>%d</info> replacements ...</comment>', basename($file), $number));

        $yaml = (new Dumper())->dump($translations, 50);
        if (!strlen($yaml)) {
            $this->output->writeln(sprintf('<error>Skipping "%s" because of empty output ...</error>', basename($file)));

            return;
        }

        return file_put_contents($file, $yaml);
    }

    /**
     * File loader
     *
     * @param SplFileInfo $file
     */
    protected function loadCsvFile(SplFileInfo $file)
    {
        $line = 0;
        if ($handle = fopen($file->getRealPath(), "r")) {
            $this->output->writeln(sprintf('<comment>Importing from "%s" ...</comment>', $file->getRealPath()));
            while ($row = fgetcsv($handle, 0, ";")) {
                if ($line) {
                    list($id, $path, $domain, $keyName, $content, $num) = $row;
                    $path = str_replace('../', '', $path);
                    if (!$this->isUTF8($content)) {
                        $content = utf8_encode($content);
                    }
                    if (!isset($this->translations[$path][$domain]) && !isset($this->count[$path][$domain])) {
                        $this->translations[$path][$domain] = array();
                        $this->count[$path][$domain] = 0;
                    }
                    $this->translations[$path][$domain] = array_replace_recursive(
                        $this->translations[$path][$domain],
                        $this->getStructuredKey(explode('.', $keyName), $content)
                    );
                    $this->count[$path][$domain]++;
                }
                $line++;
            }
            fclose($handle);
        } else {
            $this->output->writeln(sprintf('<error>Permissions error when reading "%s" ...</error>', $file->getRealPath()));
        }
    }

    /**
     * Translations importer from YAML
     *
     * @param string $file
     * @return array
     */
    protected function getYamlTranslations($file)
    {
        $translations = array();

        if (file_exists($file) && is_file($file)) {
            $translations = (new Parser())->parse(file_get_contents($file));
        }

        return $translations;
    }

    /**
     * Recursive structured key getter
     *
     * @param array $indexes
     * @param string $value
     * @return array
     */
    protected function getStructuredKey($indexes, $value)
    {
        $index = $indexes[0];
        array_shift($indexes);

        return array($index => count($indexes) ? $this->getStructuredKey($indexes, $value) : $value);
    }

    /**
     * Translations file name constructor
     *
     * @param string $path
     * @param string $domain
     * @return string
     */
    protected function getTranslationsFileName($path, $domain)
    {
        $file = $this->getContainer()->getParameter('kernel.root_dir')."/../".$path."/".$domain.".".$this->values['locale'].".".$this->values['output'];

        return $file;
    }

    /**
     * UTF8 detector
     *
     * @param string $string
     * @return boolean
     */
    protected function isUTF8($string)
    {
        return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);
    }
}
