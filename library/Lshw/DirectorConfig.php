<?php

namespace Icinga\Module\Lshw;

use Icinga\Application\Config;
use Icinga\Application\Modules\Module;
use Icinga\Module\Director\Db;
use Icinga\Module\Director\Objects\IcingaCommand;

class DirectorConfig
{
    /** @var Db */
    protected $db;

    public function commandExists(IcingaCommand $command)
    {
        return IcingaCommand::exists($command->getObjectName(), $this->db);
    }

    public function commandDiffers(IcingaCommand $command)
    {
        return IcingaCommand::load($command->getObjectName(), $this->db)
            ->replaceWith($command)
            ->hasBeenModified();
    }

    public function sync()
    {
        $windows = $this->syncCommand($this->createWindowsCommand());
        $linux = $this->syncCommand($this->createLinuxCommand());
        $linuxUnprivileged = $this->syncCommand($this->createLinuxCommandUnprivileged());

        return $windows || $linux || $linuxUnprivileged;
    }

    public function syncCommand(IcingaCommand $command)
    {
        $db = $this->db;

        $name = $command->getObjectName();
        if ($command::exists($name, $db)) {
            $new = $command::load($name, $db)
                ->replaceWith($command);
            if ($new->hasBeenModified()) {
                $new->store();

                return true;
            } else {
                return false;
            }
        } else {
            $command->store($db);

            return true;
        }
    }

    /**
     * @return IcingaCommand
     */
    public function createLinuxCommand()
    {
        return IcingaCommand::create([
            'methods_execute' => 'PluginCheck',
            'object_name' => 'lshw',
            'object_type' => 'object',
            'command'     => '/usr/bin/sudo /usr/bin/lshw -json',
        ], $this->db());
    }
    /**
     * @return IcingaCommand
     */
    public function createLinuxCommandUnprivileged()
    {
        return IcingaCommand::create([
            'methods_execute' => 'PluginCheck',
            'object_name' => 'lshw-unprivileged',
            'object_type' => 'object',
            'command'     => '/usr/bin/lshw -json 2> /dev/null',
            'is_string'     => 'y',
        ], $this->db());
    }
    /**
     * @return IcingaCommand
     */
    public function createWindowsCommand()
    {
        $content="Error: File lshw.ps1 not found!";
        $file = Module::get('lshw')->getBaseDir().DIRECTORY_SEPARATOR."contrib".DIRECTORY_SEPARATOR."psh".DIRECTORY_SEPARATOR."lshw.ps1";
        if(file_exists($file)){
            $content = file_get_contents($file);
            $content = str_replace('$','$$',$content);
            $content = str_replace("\n",' ',$content);
            $content = str_replace("\r",' ',$content);
            while(strpos($content,"  ") !== false){
                $content = str_replace("  ",' ',$content);
            }
            if (!mb_check_encoding($content, 'UTF-8')) {
                // If not, convert it to UTF-8
                $content = mb_convert_encoding($content, 'UTF-8');
            }
        }
        return IcingaCommand::create([
            'methods_execute' => 'PluginCheck',
            'object_name' => 'lshw-win',
            'object_type' => 'object',
            'command'     => 'C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe',
            'arguments'   => ['-C'=>$content]
        ], $this->db());
    }

    public function db()
    {
        if ($this->db === null) {
            $this->db = $this->initializeDb();
        }

        return $this->db;
    }

    protected function initializeDb()
    {
        $resourceName = Config::module('director')->get('db', 'resource');
        return Db::fromResourceName($resourceName);
    }
}
