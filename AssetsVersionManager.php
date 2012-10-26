<?php
namespace Kachkaev\AssetsVersionBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Works with parameters.yml at a low level, extracts and writes back value of assets_version
 * Because parsing is done using regular expressions insead of yaml, formatting is preserved when writing value to file.
 * 
 * @author Alexander Kachkaev <alexander@kachkaev.ru>
 *
 */
class AssetsVersionManager
{
    static protected $versionMask = '[a-zA-Z0-9_\.-]*';

    protected $fileName;
    protected $parameterName;

    protected $fileContents;
    protected $versionValue;
    protected $versionStartPos;

    public function __construct($fileName, $parameterName)
    {
        $this->fileName = $fileName;
        $this->parameterName = $parameterName;
        $this->readFile();
    }

    /**
     * Gets the value of assets version found in parameters file
     * 
     * @param boolean $rereadFile - if true, re-reads the file first
     */
    public function getVersion($rereadFile = false)
    {
        if ($rereadFile)
            $this->readFile();

        return $this->versionValue;
    }

    /**
     * Sets a new value for assets version found in parameters file
     * 
     * Assets version must consist only of letters, numbers and the following characters: .-_
     * 
     * @param boolean $rereadFile - if true, re-reads the file first
     */
    public function setVersion($value, $rereadFile = false)
    {
        // Checking value
        if (!preg_match('/^' . static::$versionMask . '$/', $value)) {
            throw new \InvalidArgumentException(
                    'Wrong value for assets version: "'.$value.'". It must consist only of letters, numbers and the following characters: .-_');
        }

        // Checking if file is writable
        if (!is_writable($this->fileName))
            throw new InvalidConfigurationException(
                    'Could not use "' . $this->fileName
                            . '" - only yaml files are supported by AssetsVersionManager');

        // Updating contents
        $this->fileContents = substr_replace($this->fileContents, $value,
                $this->versionStartPos, strlen($this->versionValue));
        $this->versionValue = $value;

        // Writing to file
        file_put_contents($this->fileName, $this->fileContents);
    }

    /**
     * Increments value for assets version found in parameters file
     * Only works when current value is integer or has integer ending, e.g. v42
     * If delta is given, incrementing is done by that value.
     * 
     * @param int $delta number to increment (default is 1)
     * @param boolean $rereadFile if true, re-reads the file first
     */
    public function incrementVersion($delta = 1, $rereadFile = false)
    {
        if ($rereadFile)
            $this->readFile();

        // Checking delta
        if (!is_numeric($delta) || round($delta) != $delta)
            throw new \InvalidArgumentException(
                    'Delta must be integer, "' . $delta . '" given.');

        // Parsing version value
        preg_match('/^(.*)(\d+)$/U', $this->versionValue, $matches);
        if (!array_key_exists(2, $matches)) {
            throw new \UnexpectedValueException(
                    'Could not increment assets version "'
                            . $this->versionValue
                            . '" - it should be integer or at least have integer ending.');
        }

        // Saving new value
        $this->setVersion($matches[1] . max(0, $matches[2] + $delta));
    }

    /**
     * Reads and parses file with parameters
     * 
     * @throws InvalidConfigurationException
     * @throws FileException
     * @throws \Exception
     */
    protected function readFile()
    {

        // Checking if file extension is supported
        $fileExtension = pathinfo($this->fileName, PATHINFO_EXTENSION);
        if ($fileExtension != 'yml' && $fileExtension != 'yaml')
            throw new InvalidConfigurationException(
                    'Could not use "' . $this->fileName
                            . '" - only yaml files are supported by AssetsVersionManager');

        // Reading file
        try {
            $this->fileContents = file_get_contents($this->fileName);
        } catch (\Exception $e) {
            throw new FileException(
                    'Could not read file ' . $this->fileName
                            . '. Make sure it exists and you have enough permissions.');
        }

        // Finding a row with corresponding parameter
        preg_match(
                '/(\s+' . $this->parameterName . '\:[^\S\n]*)('
                        . static::$versionMask . ')\s*\n/',
                $this->fileContents . '\n', $matches);
        if (array_key_exists(2, $matches)) {
            $this->versionValue = $matches[2];
            $this->versionStartPos = strpos($this->fileContents, $matches[0])
                    + strlen($matches[1]);
            return;
        }

        throw new \Exception(
                'Could not find definition of "' . $this->parameterName
                        . '". Make sure it exists in "' . $this->fileName
                        . '".');
    }
}
