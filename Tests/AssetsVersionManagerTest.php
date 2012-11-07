<?php

namespace Kachkaev\AssetsVersionBundle\Tests;
use Symfony\Component\Filesystem\Filesystem;

use Kachkaev\AssetsVersionBundle\AssetsVersionManager;
use Symfony\Component\Finder\Finder;

class AssetsVersionManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $fileName = 'parameters';
    protected $parameterName = 'assets_version';
    protected $fileDir;
    
    protected $fileSystem;

    protected $templates;
    protected $supportedFileFormats = array('yml');

    public function __construct()
    {
        $this->fileSystem = new Filesystem();
        
        $this->fileDir = sys_get_temp_dir() . '/assets_version_test';
        
        $this->fileSystem->mkdir($this->fileDir);

        $this->loadTemplates();
    }

    public function __destruct()
    {
        $this->fileSystem->remove($this->fileDir);
    }

    public function testGetVersion()
    {
        foreach ($this->supportedFileFormats as $currentFormat) {
            if (array_key_exists('valid', $this->templates[$currentFormat])) {
                foreach ($this->templates[$currentFormat]['valid'] as $templateName => $template) {
                    $this->setTempFileContents($currentFormat, $template, "some-version");

                    $manager = new AssetsVersionManager(
                            $this->fileDir . '/' . $this->fileName . '.'
                                    . $currentFormat, $this->parameterName);
                    $this->assertEquals($manager->getVersion(), "some-version");
                }
            }
        }
    }

    public function testSetVersion()
    {
        $versions = array('some-other-version', 1, 1234, '00001', 'v42', '');

        foreach ($this->supportedFileFormats as $currentFormat) {
            if (array_key_exists('valid', $this->templates[$currentFormat])) {
                foreach ($this->templates[$currentFormat]['valid'] as $templateName => $template) {
                    $this->setTempFileContents($currentFormat, $template, "some-version");

                    $fileName = $this->fileDir . '/' . $this->fileName . '.'
                                    . $currentFormat;
                    $manager = new AssetsVersionManager(
                            $fileName, $this->parameterName);

                    foreach ($versions as $version) {
                        // Checking getter
                        $manager->setVersion($version);
                        $this->assertEquals($manager->getVersion(), $version);
                        
                        // Checking real file contents
                        $newTempFileContents = $this->getTempFileContents($currentFormat);
                        $this->setTempFileContents($currentFormat, $template, $version);
                        $this->assertEquals($newTempFileContents, $this->getTempFileContents($currentFormat));
                        $this->assertEquals($manager->getVersion(), $version);
                    }
                }
            }
        }
    }

    public function testSetInvalidVersion()
    {
        $versions = array(null, ' ', array(), '12345 ');

        foreach ($this->supportedFileFormats as $currentFormat) {
            if (array_key_exists('valid', $this->templates[$currentFormat])) {
                foreach ($this->templates[$currentFormat]['valid'] as $templateName => $template) {
                    $this->setTempFileContents($currentFormat, $template, "some-version");

                    $manager = new AssetsVersionManager(
                            $this->fileDir . '/' . $this->fileName . '.'
                                    . $currentFormat, $this->parameterName);

                    foreach ($versions as $version) {
                        try {
                            $manager->setVersion($version);
                            $this
                                    ->assertEquals($manager->getVersion(),
                                            $version);
                        } catch (\InvalidArgumentException $e) {
                            continue;
                        }
                        $this
                                ->fail(
                                        'InvalidArgumentException was expected for '
                                                . var_export($version, true)
                                                . ' as a new value for '
                                                . $this->parameterName);
                    }
                }
            }
        }
    }

    public function testIncrementVersion()
    {
        $versions = array(
                '18' => array("1" => '19', "10" => '28', "-100" => '0'),
                '42' => array("1" => '43', "10" => '52', "-100" => '0'),
                'v42' => array("1" => 'v43', "10" => 'v52', "-100" => 'v0'),
                '1.1' => array("1" => '1.2', "10" => '1.11', "-100" => '1.0'),
                'v0042' => array("1" => 'v0043', "10" => 'v0052',
                        "-100" => 'v0000'));

        foreach ($this->supportedFileFormats as $currentFormat) {
            if (array_key_exists('valid', $this->templates[$currentFormat])) {
                foreach ($this->templates[$currentFormat]['valid'] as $templateName => $template) {
                    foreach ($versions as $version => $increments) {
                        foreach ($increments as $increment => $result) {
                            $this->setTempFileContents($currentFormat, $template, $version);

                            $manager = new AssetsVersionManager(
                                    $this->fileDir . '/' . $this->fileName
                                            . '.' . $currentFormat,
                                    $this->parameterName);

                            $manager->incrementVersion($increment);
                            $this
                                    ->assertEquals($manager->getVersion(),
                                            $result);
                        }
                    }
                }
            }
        }
    }

    public function testIncrementVersionWithNoNumbers()
    {
        $versions = array('test', '42x');

        foreach ($this->supportedFileFormats as $currentFormat) {
            if (array_key_exists('valid', $this->templates[$currentFormat])) {
                foreach ($this->templates[$currentFormat]['valid'] as $templateName => $template) {
                    foreach ($versions as $version) {
                        $this->setTempFileContents($currentFormat, $template, $version);

                        $manager = new AssetsVersionManager(
                                $this->fileDir . '/' . $this->fileName . '.'
                                        . $currentFormat, $this->parameterName);

                        try {
                            $manager->incrementVersion();
                        } catch (\UnexpectedValueException $e) {
                            continue;
                        }
                        $this
                                ->fail(
                                        'UnexpectedValueException was expected for '
                                                . var_export($version, true)
                                                . ' when trying to increment it');
                    }
                }
            }
        }
    }

    public function testIncrementByWrongValue()
    {
        $increments = array('', null, array(), 'test');

        foreach ($this->supportedFileFormats as $currentFormat) {
            if (array_key_exists('valid', $this->templates[$currentFormat])) {
                foreach ($this->templates[$currentFormat]['valid'] as $templateName => $template) {
                    foreach ($increments as $increment) {
                        $this->setTempFileContents($currentFormat, $template, 'v42');

                        $manager = new AssetsVersionManager(
                                $this->fileDir . '/' . $this->fileName . '.'
                                        . $currentFormat, $this->parameterName);

                        try {
                            $manager->incrementVersion($increment);
                        } catch (\InvalidArgumentException $e) {
                            continue;
                        }
                        $this
                                ->fail(
                                        'InvalidArgumentException was expected when trying to increment a version by '
                                                . var_export($version, true));
                    }
                }
            }
        }
    }

    public function testMalformedFiles()
    {
        foreach ($this->supportedFileFormats as $currentFormat) {
            if (array_key_exists('invalid', $this->templates[$currentFormat])) {
                foreach ($this->templates[$currentFormat]['invalid'] as $templateName => $template) {
                    $this->setTempFileContents($currentFormat, $template);

                    try {
                        $manager = new AssetsVersionManager(
                                $this->fileDir . '/' . $this->fileName . '.'
                                        . $currentFormat, $this->parameterName);
                    } catch (\Exception $e) {
                        continue;
                    }
                    $this
                            ->fail(
                                    'An exception was expected when readig a malformed file '
                                            . $this->fileName . '.'
                                            . $currentFormat);
                }
            }
        }
    }

    public function testNonYamls()
    {

    }

    protected function getFullPathToFile($fileFormat)
    {
        return $this->fileDir . '/' . $this->fileName . '.' . $fileFormat;
    }
    
    protected function setTempFileContents($fileFormat, $template, $version = null)
    {
        $fileContents = $template;

        if (null === $version) {
            $fileContents = str_replace('%VERSION%', 'null', $fileContents);
        } else {
            $fileContents = str_replace('%VERSION%', $version, $fileContents);
        }

        file_put_contents(
                $this->getFullPathToFile($fileFormat),
                $fileContents);
    }
    
    protected function getTempFileContents($fileFormat)
    {
        return file_get_contents($this->getFullPathToFile($fileFormat));
    }
    
    protected function loadTemplates()
    {
        $this->templates = array();

        $formatFinder = new Finder();
        $formatFinder->directories()->in(__DIR__ . '/Resources/templates')
                ->depth(0);

        foreach ($formatFinder as $formatDir) {
            $format = $formatDir->getFilename();
            $this->templates[$format] = array();

            $groupFinder = new Finder();
            $groupFinder->directories()->in($formatDir->getPathname())
                    ->depth(0);

            foreach ($groupFinder as $groupDir) {
                $group = $groupDir->getFilename();
                $this->templates[$format][$group] = array();

                $fileFinder = new Finder();
                $fileFinder->files()->name('*.' . $format)
                        ->in($groupDir->getPathname());

                foreach ($fileFinder as $file) {
                    $name = $file->getBasename('.' . $format);
                    $this->templates[$format][$group][$name] = file_get_contents(
                            $file->getPathname());
                }
            }
        }
    }
}
