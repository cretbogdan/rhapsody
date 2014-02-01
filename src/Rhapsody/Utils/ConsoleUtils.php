<?php

namespace Rhapsody\Utils;

use Symfony\Component\Console;
use Symfony\Component\Console\Output\ConsoleOutput;

class ConsoleUtils
{
    protected static $output;

    public static function getOutput()
    {
        if (null === static::$output) {
            static::$output = new ConsoleOutput();
        }

        return static::$output;
    }

    public static function writeln($message, $style = null)
    {
        if (null === $style) {
            static::getOutput()->writeln($message);
        } else {
            static::getOutput()->writeln("<$style>".$message."</$style>");
        }
    }

    public static function write($message, $style = null)
    {
        if (null === $style) {
            static::getOutput()->write($message);
        } else {
            static::getOutput()->write("<$style>".$message."</$style>");
        }
    }

    public static function sleep($seconds)
    {
        static::writeln("Sleeping $seconds seconds ...", 'info');
        sleep($seconds);
    }

    /**
     * Register commands in a given directory starting with namespace of the directory's name
     *
     * @param  Console\Application $console
     * @param  string              $commandsDirectory   Absolute path
     */
    public static function registerCommands(Console\Application $console, $commandsDirectory, array $notNames = array(), array $names = array('*Command.php'))
    {
        if (! is_dir($commandsDirectory)) {
            throw new \InvalidArgumentException("$commandsDirectory is not a directory!");
        }

        $finder = \Symfony\Component\Finder\Finder::create()->files()->in($commandsDirectory);

        foreach ($names as $name) {
            $finder->name($name);
        }

        foreach ($notNames as $name) {
            $finder->notName($name);
        }

        foreach ($finder as $file) {
            $namespace = self::findNamespace($file->getRealPath());
            $command = $namespace."\\".rtrim($file->getFilename(), ".php");

            if (class_exists($command)) {
                $console->add(new $command());
            } else {
                ConsoleUtils::writeln("Command class \"$command\" does not exist!");
            }
        }
    }

    public static function findNamespace($filepath)
    {
        $namespace = '';
        $file = new \SplFileObject($filepath);

        do {
            $continue = true;
            $line = $file->fgets();

            if (stripos($line, 'namespace') !== false) {
                $namespace = preg_replace('/^(.*)namespace (.*);(.*)$/i', '$2', $line);
                $namespace = trim($namespace);
                $continue = false;
            }

        } while ($continue);

        return $namespace;
    }
}




