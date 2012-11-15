<?php

namespace elseym\AgatheBundle\Composer;

use Composer\Script\CommandEvent;

class ScriptHandler
{
    public static function linkBinaries(CommandEvent $event) {
        $event->getIO()->write("Linking agathe binaries... ", false);

        $extra = $event->getComposer()->getPackage()->getExtra();
        $sourceDir = realpath(dirname(__FILE__) . "/../Resources/bin");
        $destinationDir = realpath($extra['agathe-bin-dir']);

        foreach (scandir($sourceDir) as $sourceFileName) {
            if (preg_match("/\.?\.$/", $sourceFileName)) continue;

            $sourceFile = $sourceDir . "/" . $sourceFileName;
            $destinationFile = $destinationDir . "/" . $sourceFileName;

            if (is_link($destinationFile)) {
                unlink($destinationFile);
            } elseif (is_file($destinationFile)) {
                throw new \Exception("File '$destinationFile' already exists and is not a symlink.");
            }

            if (!symlink($sourceFile, $destinationFile)) {
                throw new \Exception("Failed to create symlink for '$sourceFile' in '$destinationDir'!");
            }
        }

        $event->getIO()->write("ok.");
    }
}