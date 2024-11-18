<?php

namespace Icinga\Module\Lshw\ProvidedHook\Icingadb;

use Icinga\Application\Logger;
use Icinga\Module\Icingadb\Hook\ServiceDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Lshw\Web\Widget\LshwHardwareTree;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Html\ValidHtml;

class ServiceDetailExtension extends ServiceDetailExtensionHook
{

    public function getHtmlForObject(Service $service): ValidHtml
    {
        $output = $service->state->output . "\n" . $service->state->long_output;
        $commandName = $service->checkcommand_name;
        $div = Html::tag("div");
        if($commandName == "lshw" || $commandName == "lshw-unprivileged" || $commandName == "lshw-win") {
            if(isset($service->state->output) && isset($service->state->long_output)){
                $test = json_decode($output, true);

                if(is_array($test)){

                    $h2 = Html::tag("h2",null, "Hardware Info");
                    $div->add($h2);
                    try{
                        $div->add(new LshwHardwareTree($output));
                    }catch ( \Throwable $e) {
                        $div->add(Html::tag("p",null,"Check Output unsupported:"));
                        $div->add(Html::tag("p",null,"Output:\n".$output));
                    }
                }else{
                    $div->add(Html::tag("p",null,"Check Output unsupported:"));
                    $div->add(Html::tag("p",null,"Output:\n".$output));
                    return $div;
                }
            }else{
                $div->add(Html::tag("p",null,"Check Output unsupported:"));
                $div->add(Html::tag("p",null,"Output:\n".$output));
                return $div;
            }



        }
        return $div;
    }
}