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
    public static function registerCommands(Console\Application $console, $commandsDirectory)
    {
        $directoryInfo = new \SplFileInfo($commandsDirectory);

        if (! $directoryInfo->isDir()) {
            throw new \InvalidArgumentException("$commandsDirectory is not a directory!");
        }

        $namespace = $directoryInfo->getFilename();

        $filesystem = new \Symfony\Component\Filesystem\Filesystem();

        $finder = \Symfony\Component\Finder\Finder::create()
            ->files()
            ->in($commandsDirectory)
            ->name('*Command.php');

        foreach ($finder as $file) {
            $command = '/'.$filesystem->makePathRelative($file->getRealPath(), $commandsDirectory);
            $command = preg_replace("/(.*)$namespace(.*)/i", "\\$namespace$2", $command);
            $command = str_replace('.php/', '', $command);
            $command = str_replace('/', '\\', $command);

            if (class_exists($command)) {
                $console->add(new $command());
            } else {
                ConsoleUtils::writeln("Command class \"$command\" does not exist!");
            }
        }
    }
}




