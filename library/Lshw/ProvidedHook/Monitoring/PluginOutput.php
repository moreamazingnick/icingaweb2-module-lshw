<?php

namespace Icinga\Module\Lshw\ProvidedHook\Monitoring;

use Icinga\Module\Monitoring\Hook\PluginOutputHook;

class PluginOutput extends PluginOutputHook
{
    public function render($command, $output, $detail)
    {
        if(strlen($output) > 10 ){
            return "Plugin Output rendered to Detail View";
        }else{
            return "";
        }

    }

    public function getCommands()
    {
        return ['lshw','lshw-unprivileged', 'lshw-win'];
    }
}
