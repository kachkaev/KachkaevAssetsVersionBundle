<?php

namespace Kachkaev\AssetsVersionBundle\Tests;
use Symfony\Component\Filesystem\Filesystem;

use Kachkaev\AssetsVersionBundle\AssetsVersionManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class AssetsVersionManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $fileName = 'parameters';
    protected $validParameterNames = array('assets_version');
    protected $invalidParameterNames = array('assets_version ', '123');
    protected $fileDir;

    protected $fileSystem;

    protected $templates;
    protected $supportedFileFormats = array('yml');
    protected $unsupportedFileFormats = array('php', 'xml');

    public function __construct()
    {
        $this->fileSystem = new Filesystem();

        $this->fileDir = sys_get_temp_dir() . '/assets_version_test';

        $this->fileSystem->mkdir($this->fileDir);

        $this->loadTemplates();
    }

    public function setUp()
    {
        $this->fileSystem->mkdir($this->fileDir);
    }

    public function tearDown()
    {
        $this->fileSystem->remove($this->fileDir);
    }

    public function testInvalidParameterNames()
    {
        foreach ($this->supportedFileFormats as $format) {
            foreach ($this->templates[$format]['valid'] as $templateName => $template) {
                foreach ($this->invalidParameterNames as $parameterName) {
                    $this->setTempFileContents($format, $template, $parameterName, 'some-version');

                    $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
                    try {
                        $manager = new AssetsVersionManager($filePath, $parameterName);
                    } catch (\InvalidArgumentException $e) {
                        continue;
                    }
                    $this->fail(sprintf(
                            'InvalidArgumentException was expected for an invalid parameter name %s',
                            $parameterName
                        ));
                }
            }
        }
    }

    public function testGetVersion()
    {
        foreach ($this->supportedFileFormats as $format) {
            foreach ($this->templates[$format]['valid'] as $templateName => $template) {
                foreach ($this->validParameterNames as $parameterName) {
                    $this->setTempFileContents($format, $template, $parameterName, 'some-version');

                    $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
                    $manager = new AssetsVersionManager($filePath, $parameterName);

                    $this->assertEquals($manager->getVersion(), 'some-version');
                }
            }
        }
    }

    public function testSetVersion()
    {
        $versions = array('some-other-version', 1, 1234, '00001', 'v42', 'v042', '');

        foreach ($this->supportedFileFormats as $format) {
            foreach ($this->templates[$format]['valid'] as $templateName => $template) {
                foreach ($this->validParameterNames as $parameterName) {
                    $this->setTempFileContents($format, $template, $parameterName, 'some-version');

                    $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
                    $manager = new AssetsVersionManager($filePath, $parameterName);

                    foreach ($versions as $version) {
                        // Checking setter
                        $manager->setVersion($version);
                        $this->assertEquals($manager->getVersion(), $version);

                        // Checking real file contents
                        $newTempFileContents = $this->getTempFileContents($format);
                        $this->setTempFileContents($format, $template, $parameterName, $version);
                        $this->assertEquals($newTempFileContents, $this->getTempFileContents($format));
                        $this->assertEquals($manager->getVersion(), $version);
                    }
                }
            }
        }
    }

    public function testSetInvalidVersion()
    {
        $versions = array(null, ' ', array(), '12345 ');

        foreach ($this->supportedFileFormats as $format) {
            foreach ($this->templates[$format]['valid'] as $templateName => $template) {
                foreach ($this->validParameterNames as $parameterName) {
                    $this->setTempFileContents($format, $template, $parameterName, 'some-version');

                    $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
                    $manager = new AssetsVersionManager($filePath, $parameterName);

                    foreach ($versions as $version) {
                        try {
                            $manager->setVersion($version);
                            $this->assertEquals($manager->getVersion(), $version);
                        } catch (\InvalidArgumentException $e) {
                            continue;
                        }
                        $this->fail(
                            'InvalidArgumentException was expected for '
                                    . var_export($version, true)
                                    . ' as a new value for '
                                    . $parameterName
                            );
                    }
                }
            }
        }
    }

    public function testIncrementVersion()
    {
        $versions = array(
                '18'    => array('1' => '19',    '10' => '28',    '-100' => '0'),
                '42'    => array('1' => '43',    '10' => '52',    '-100' => '0'),
                'v42'   => array('1' => 'v43',   '10' => 'v52',   '-100' => 'v0'),
                'v042'  => array('1' => 'v043',  '10' => 'v052',  '-100' => 'v000'),
                'v0042' => array('1' => 'v0043', '10' => 'v0052', '-100' => 'v0000'),
                '1.1'   => array('1' => '1.2',   '10' => '1.11',  '-100' => '1.0')
            );

        foreach ($this->supportedFileFormats as $format) {
            foreach ($this->templates[$format]['valid'] as $templateName => $template) {
                foreach ($this->validParameterNames as $parameterName) {
                    foreach ($versions as $version => $increments) {
                        foreach ($increments as $increment => $result) {
                            $this->setTempFileContents($format, $template, $parameterName, $version);

                            $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
                            $manager = new AssetsVersionManager($filePath, $parameterName);

                            $manager->incrementVersion($increment);
                            $this->assertEquals($manager->getVersion(), $result);
                        }
                    }
                }
            }
        }
    }

    public function testIncrementVersionWithNoNumbers()
    {
        $versions = array('test', '42x');

        foreach ($this->supportedFileFormats as $format) {
            foreach ($this->templates[$format]['valid'] as $templateName => $template) {
                foreach ($this->validParameterNames as $parameterName) {
                    foreach ($versions as $version) {
                        $this->setTempFileContents($format, $template, $parameterName, $version);

                        $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
                        $manager = new AssetsVersionManager($filePath, $parameterName);

                        try {
                            $manager->incrementVersion();
                        } catch (\UnexpectedValueException $e) {
                            continue;
                        }
                        $this->fail(
                                'UnexpectedValueException was expected for '
                                . var_export($version, true)
                                . ' when trying to increment it'
                            );
                    }
                }
            }
        }
    }

    public function testIncrementByWrongValue()
    {
        $increments = array('', null, array(), 'test');

        foreach ($this->supportedFileFormats as $format) {
            foreach ($this->templates[$format]['valid'] as $templateName => $template) {
                foreach ($this->validParameterNames as $parameterName) {
                    foreach ($increments as $increment) {
                        $this->setTempFileContents($format, $template, $parameterName, 'v042');

                        $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
                        $manager = new AssetsVersionManager($filePath, $parameterName);

                        try {
                            $manager->incrementVersion($increment);
                        } catch (\InvalidArgumentException $e) {
                            continue;
                        }
                        $this->fail(
                                'InvalidArgumentException was expected when trying to increment a version by '
                                . var_export($version, true)
                            );
                    }
                }
            }
        }
    }

    public function testMalformedFiles()
    {
        foreach ($this->supportedFileFormats as $format) {
            foreach ($this->templates[$format]['invalid'] as $templateName => $template) {
                foreach ($this->validParameterNames as $parameterName) {
                    $this->setTempFileContents($format, $parameterName, $template);

                    $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
                    try {
                        $manager = new AssetsVersionManager($filePath, $parameterName);
                    } catch (\Exception $e) {
                        continue;
                    }
                    $this->fail(
                            'An exception was expected when readig a malformed file '
                            . $this->fileName . '.'
                            . $format
                        );
                }
            }
        }
    }

    public function testNonYamls()
    {
        foreach ($this->unsupportedFileFormats as $format) {
            foreach ($this->templates[$format]['invalid'] as $templateName => $template) {
                foreach ($this->validParameterNames as $parameterName) {
                    $this->setTempFileContents($format, $template, $parameterName, 'some-version');

                    $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
                    try {
                        $manager = new AssetsVersionManager($filePath, $parameterName);
                     } catch (InvalidConfigurationException $e) {
                         continue;
                     }
                    $this->fail(
                            'An exception was expected when readig a file of an unsupported format '
                            . $this->fileName . '.'
                            . $format
                        );
                }
            }
        }
    }

    public function testRereadFileInGetVersion() {
        foreach ($this->supportedFileFormats as $format) {
            $templates = array_values($this->templates[$format]['valid']);
            $template = $templates[0];
            $parameterName = $this->validParameterNames[0];

            $this->setTempFileContents($format, $template, $parameterName, 'some-version');

            $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
            $manager = new AssetsVersionManager($filePath, $parameterName);

            chmod($filePath, 000);

            try {
                $manager->getVersion(true);

                chmod($filePath, 777);

                $this->fail(sprintf(
                        'FileException was expected when rereading an unavailable file %s',
                        $filePath
                    ));
            } catch (FileException $e) {
            }

            $this->assertEquals($manager->getVersion(), 'some-version');

            chmod($filePath, 777);

            $this->assertEquals($manager->getVersion(true), 'some-version');

            unlink($filePath);
        }
    }

    public function testRereadFileInIncrementVersion() {
        foreach ($this->supportedFileFormats as $format) {
            $templates = array_values($this->templates[$format]['valid']);
            $template = $templates[0];
            $parameterName = $this->validParameterNames[0];

            $this->setTempFileContents($format, $template, $parameterName, '42');

            $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
            $manager = new AssetsVersionManager($filePath, $parameterName);

            unlink($filePath);

            try {
                $manager->incrementVersion(1, true);

                $this->fail(sprintf(
                        'FileException was expected when rereading an unavailable file %s',
                        $filePath
                    ));
            } catch (FileException $e) {
            }

            $this->setTempFileContents($format, $template, $parameterName, '42');
            $manager->incrementVersion(1, true);

            $this->assertEquals($manager->getVersion(), '43');

            unlink($filePath);
        }
    }

    public function testReadUnavailableFiles()
    {
        foreach ($this->supportedFileFormats as $format) {
            $filePath = $this->fileDir . '/missing.' . $format;
            try {
                $manager = new AssetsVersionManager($filePath, $this->validParameterNames[0]);
            } catch (FileException $e) {
                continue;
            }
            $this->fail(sprintf(
                    'FileException was expected when readig an unavailable file %s',
                    $filePath
                ));
        }
    }

    public function testWriteUnavailableFiles()
    {
        foreach ($this->supportedFileFormats as $format) {
            $templates = array_values($this->templates[$format]['valid']);
            $template = $templates[0];
            $parameterName = $this->validParameterNames[0];

            $this->setTempFileContents($format, $template, $parameterName, 'some-version');

            $filePath = $this->fileDir . '/' . $this->fileName . '.' . $format;
            $manager = new AssetsVersionManager($filePath, $parameterName);

            chmod($filePath, 000);

            try {
                $manager->setVersion('some-other-value');
            } catch (FileException $e) {
                chmod($filePath, 777);
                continue;
            }
            chmod($filePath, 777);

            $this->fail(sprintf(
                    'FileException was expected when writing an unavialble file %s',
                    $filePath
                ));
        }
    }

    protected function getFullPathToFile($fileFormat)
    {
        return $this->fileDir . '/' . $this->fileName . '.' . $fileFormat;
    }

    protected function setTempFileContents($fileFormat, $template, $parameterName, $parameterValue = null)
    {
        $fileContents = $template;

        $fileContents = str_replace('<PARAMETER_NAME>', $parameterName, $fileContents);
        if (null === $parameterValue) {
            $fileContents = str_replace('<PARAMETER_VALUE>', 'null', $fileContents);
        } else {
            $fileContents = str_replace('<PARAMETER_VALUE>', $parameterValue, $fileContents);
        }

        file_put_contents(
                $this->getFullPathToFile($fileFormat),
                $fileContents
            );
    }

    protected function getTempFileContents($fileFormat)
    {
        return file_get_contents($this->getFullPathToFile($fileFormat));
    }

    protected function loadTemplates()
    {
        $this->templates = array();

        $formatFinder = new Finder();
        $formatFinder
            ->directories()
            ->in(__DIR__ . '/Resources/templates')
            ->depth(0);

        foreach ($formatFinder as $formatDir) {
            $format = $formatDir->getFilename();
            $this->templates[$format] = array();

            $groupFinder = new Finder();
            $groupFinder
                ->directories()
                ->in($formatDir->getPathname())
                ->depth(0);

            foreach ($groupFinder as $groupDir) {
                $group = $groupDir->getFilename();
                $this->templates[$format][$group] = array();

                $fileFinder = new Finder();
                $fileFinder
                    ->files()
                    ->name('*.' . $format)
                    ->in($groupDir->getPathname());

                foreach ($fileFinder as $file) {
                    $name = $file->getBasename('.' . $format);
                    $this->templates[$format][$group][$name] = file_get_contents($file->getPathname());
                }
            }
        }
    }
}
