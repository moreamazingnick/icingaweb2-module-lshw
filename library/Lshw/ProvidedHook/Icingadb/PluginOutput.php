<?php

namespace Icinga\Module\Lshw\ProvidedHook\Icingadb;

use Icinga\Application\Logger;
use Icinga\Module\Lshw\Web\Widget\LshwHardwareTree;
use ipl\Html\Html;

class PluginOutput extends \Icinga\Module\Icingadb\Hook\PluginOutputHook
{

    /**
     * Return whether the given command is supported or not
     *
     * @param string $commandName
     *
     * @return bool
     */
    public function isSupportedCommand(string $commandName): bool{
        if($commandName === "lshw" || $commandName == "lshw-unprivileged" || $commandName == "lshw-win"){
            return true;
        }
        return false;
    }

    /**
     * Process the given plugin output based on the specified check command
     *
     * Try to process the output as efficient and fast as possible.
     * Especially list view performance may suffer otherwise.
     *
     * @param string $output A host's or service's output
     * @param string $commandName The name of the checkcommand that produced the output
     * @param bool $enrichOutput Whether macros or other markup should be processed
     *
     * @return string
     */
    public function render(string $output, string $commandName, bool $enrichOutput): string{


        return "Plugin Output rendered to Detail View";


    }


}
