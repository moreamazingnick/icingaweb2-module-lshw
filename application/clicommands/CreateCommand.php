<?php


namespace Icinga\Module\Lshw\Clicommands;

use Icinga\Cli\Command;
use Icinga\Module\Lshw\DirectorConfig;


class CreateCommand extends Command
{
    /**
     * USAGE:
     *
     *   icingacli selenium create command
     */
    public function commandAction()
    {
        $config = new DirectorConfig();
        $config->sync();
        if($config->commandExists($config->createWindowsCommand()) && $config->commandExists($config->createLinuxCommand()) && $config->commandExists($config->createLinuxCommandUnprivileged())){
            echo "Commands in sync\n";
            exit(0);
        }else{
            echo "Commands not sync\n";
            exit(1);
        }

    }

}
