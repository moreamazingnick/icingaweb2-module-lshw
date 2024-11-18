<?php

namespace Icinga\Module\Lshw\ProvidedHook\Monitoring;

use Icinga\Application\Logger;
use Icinga\Module\Lshw\Web\Widget\LshwHardwareTree;
use Icinga\Module\Monitoring\Hook\DetailviewExtensionHook;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use ipl\Html\Html;

class DetailviewExtension extends DetailviewExtensionHook
{

    public function getHtmlForObject(MonitoredObject $service)
    {
        $div = Html::tag("div");

        if(get_class($service) ==="Icinga\Module\Monitoring\Object\Service" ){
            $output = $service->service_output . "\n" . $service->service_long_output;
            $output = str_replace('\n',' ',$output);
            $commandName = $service->service_check_command;
            if($commandName == "lshw" || $commandName == "lshw-unprivileged" || $commandName == "lshw-win") {
                if(isset($service->service_output) && isset($service->service_long_output)){
                    $test = json_decode($output, true);

                    if(is_array($test)){

                        $h2 = Html::tag("h2",null, "Hardware Info");
                        $div->add($h2);
                        try{
                            $div->add(new LshwHardwareTree($output));
                        }catch ( \Throwable $e) {
                            $div->add(Html::tag("p",null,"Check Output unsupported:"));
                            $div->add(Html::tag("p",null,"Output:\n".$output));
                            Logger::debug('lshw: '. $e->getMessage());
                            Logger::debug('lshw: '. $e->getTraceAsString());
                        }
                    }else{
                        $div->add(Html::tag("p",null,"Check Output unsupported:"));
                        $div->add(Html::tag("p",null,"Output:\n".$output));
                        Logger::debug('lshw: '. 'falied decode');
                        return $div;
                    }
                }else{
                    $div->add(Html::tag("p",null,"Check Output unsupported:"));
                    $div->add(Html::tag("p",null,"Output:\n".$output));
                    Logger::debug('lshw: '. 'something missing');

                    return $div;
                }



            }
        }

        return $div;
    }
}