<?php
namespace Kachkaev\AssetsVersionBundle;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Works with parameters.yml at a low level, extracts and writes back value of assets_version
 * Because parsing is done using regular expressions insead of YamlParser, formatting is preserved when writing value to file.
 *
 * @author Alexander Kachkaev <alexander@kachkaev.ru>
 *
 */
class AssetsVersionManager
{
    static protected $versionParameterMask = '[a-zA-Z_][a-zA-Z0-9_-]*';
    static protected $versionValueMask = '[a-zA-Z0-9_\.-]*';

    protected $fileName;
    protected $parameterName;

    protected $fileContents;
    protected $versionValue;
    protected $versionStartPos;

    public function __construct($fileName, $parameterName)
    {
        $this->fileName = $fileName;

        if (!preg_match('/^' . static::$versionParameterMask . '$/', $parameterName)) {
            throw new \InvalidArgumentException(sprintf(
                    'Wrong value for parameter name %s - it should consist only of characters, numbers and dash or underscore',
                    var_export($parameterName, true)
                ));
        }
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
        if ($rereadFile) {
            $this->readFile();
        }

        return $this->versionValue;
    }

    /**
     * Sets a new value for the assets version parameter
     *
     * Assets version must consist only of letters, numbers and the following characters: .-_
     */
    public function setVersion($value)
    {
        if (!is_string($value) && !is_numeric($value)) {
            throw new \InvalidArgumentException(sprintf(
                    'Wrong value for assets version: %s - it must be string or numeric',
                    var_export($value, true)
                ));
        }

        if (!preg_match('/^' . static::$versionValueMask . '$/', $value)) {
            throw new \InvalidArgumentException(sprintf(
                    'Wrong value for assets version: %s - it must be empty or consist only of letters, numbers and the following characters: .-_',
                    var_export($value, true)
                ));
        }

        $this->fileContents = substr_replace(
                $this->fileContents,
                $value,
                $this->versionStartPos,
                strlen($this->versionValue)
            );
        $this->versionValue = $value;

        try {
            file_put_contents($this->fileName, $this->fileContents);
        } catch (\Exception $e) {
            throw new FileException(sprintf(
                    'Could not write to write "%s"; make sure it exists and you have enough permissions',
                    $this->fileName
                ));
        }
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
        if ($rereadFile) {
            $this->readFile();
        }

        if (!is_numeric($delta) || round($delta) != $delta) {
            throw new \InvalidArgumentException(sprintf(
                    'Delta must be integer, %s given',
                    var_export($delta, true)
                ));
        }

        preg_match('/^(.*)(\d+)$/U', $this->versionValue, $matches);
        if (!array_key_exists(2, $matches)) {
            throw new \UnexpectedValueException(sprintf(
                    'Could not increment assets version %s - it should be integer or at least have integer ending',
                    var_export($this->versionValue, true)
                ));
        }

        $newValue = max(0, $matches[2] + $delta) . '';

        // Preserve leading zeros
        if ($matches[2][0] == '0') {
            $newValue = str_pad($newValue, strlen($matches[2]), '0', STR_PAD_LEFT);
        }

        $this->setVersion($matches[1] . $newValue);
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

        $fileExtension = pathinfo($this->fileName, PATHINFO_EXTENSION);
        if ($fileExtension != 'yml' && $fileExtension != 'yaml')
            throw new InvalidConfigurationException(sprintf(
                    'Could not use "%s" - only yml files are supported by AssetsVersionManager',
                    var_export($this->versionValue, true)
                ));

        try {
            $this->fileContents = file_get_contents($this->fileName);
        } catch (\Exception $e) {
            throw new FileException(sprintf(
                    'Could not read file "%s"; make sure it exists and you have enough permissions',
                    $this->fileName
                ));
        }

        // Find a row with the parameter
        preg_match(
                '/(\s+' . $this->parameterName . '\:[^\S\n]*)(' . static::$versionValueMask . ')\s*(\n|#)/',
                $this->fileContents . "\n",
                $matches
            );

        if (array_key_exists(2, $matches)) {
            $this->versionValue = $matches[2];
            $this->versionStartPos = strpos($this->fileContents."\n", $matches[0]) + strlen($matches[1]);
            return;
        }

        throw new \Exception(sprintf(
                'Could not find definition of parameter "%s"; make sure it exists in "%s"',
                $this->parameterName,
                $this->fileName
            ));
    }
}
