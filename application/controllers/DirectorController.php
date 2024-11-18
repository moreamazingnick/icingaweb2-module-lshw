<?php

namespace Icinga\Module\Lshw\Controllers;

use Exception;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Director\Objects\IcingaCommand;
use Icinga\Module\Lshw\DirectorConfig;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Tab;
use Icinga\Web\Widget\Tabs;
use ipl\Html\Html;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Link;

class DirectorController extends CompatController
{
    /**
     * @throws \Icinga\Security\SecurityException
     */
    public function init()
    {
        $this->assertPermission('config/modules');
    }


    protected function runFailSafe($callable)
    {
        try {
            if (is_array($callable)) {
                return call_user_func($callable);
            } elseif (is_string($callable)) {
                return call_user_func([$this, $callable]);
            } else {
                return $callable();
            }
        } catch (Exception $e) {
            $this->addContent(
                Html::tag('p', ['class' => 'state-hint error'], sprintf(
                    $this->translate('ERROR: %s'),
                    $e->getMessage()
                ))
            );
            // $this->addContent(Html::tag('pre', null, $e->getTraceAsString()));

            return false;
        }
    }



    public function indexAction()
    {
        $this->assertPermission('director/admin');
        $this->mergeTabs($this->Module()->getConfigTabs()->activate('config/director'));

        if ($this->params->get('action') === 'sync') {
            $this->runFailSafe('sync');
            return;
        }
        $this->addControl(new ActionLink(
            'Sync to Director',
            Url::fromPath('lshw/director', ['action' => 'sync']),
            'sync'
        ));
        $this->runFailSafe(function () {
            try {
                $config = new DirectorConfig();
                $this->addCommand($config->createLinuxCommand(), $config);
                $this->addCommand($config->createLinuxCommandUnprivileged(), $config);
                $this->addCommand($config->createWindowsCommand(), $config);
            } catch (ConfigurationError $e) {
                $this->addContent(
                    Html::tag('h1', ['class' => 'state-hint error'], $this->translate(
                        'Icinga Director has not been configured on this system: %s',
                        $e->getMessage()
                    ))
                );
            }
        });
    }

    protected function sync()
    {
        $config = new DirectorConfig();
        if ($config->sync()) {
            Notification::success('Commands have been updated in Icinga Director');
        } else {
            Notification::success('Nothing changed, commands are fine');
        }
        $this->redirectNow($this->getRequest()->getUrl()->without('action'));
    }

    /**
     * @param IcingaCommand $command
     * @param DirectorConfig $config
     */
    protected function addCommand(IcingaCommand $command, DirectorConfig $config)
    {
        $name = $command->getObjectName();
        $this->addContent(Html::tag('h1', null, $name));
        if ($config->commandExists($command)) {
            $link = new Link(
                $name,
                Url::fromPath('director/command', ['name' => $name]),
                ['data-base-target' => '_next']
            );

            if ($config->commandDiffers($command)) {
                $this->addContent($this->createHint(
                    Html::sprintf(
                        'The CheckCommand %s exists but differs in your Icinga Director',
                        $link
                    ),
                    'warning'
                ));
            } else {
                $this->addContent($this->createHint(
                    Html::sprintf(
                        'The CheckCommand definition for %s is fine',
                        $link
                    ),
                    'ok'
                ));
            }
        } else {
            $this->addContent($this->createHint(
                'Command does not exist in your Icinga Director',
                'warning'
            ));
        }
        $this->addContent(Html::tag('pre', null, (string) $command));
    }

    protected function createHint($msg, $state)
    {
        return Html::tag('p', ['class' => ['state-hint', $state]], $msg);
    }

    /**
     * Merge tabs with other tabs contained in this tab panel
     *
     * @param Tabs $tabs
     *
     * @return void
     */
    protected function mergeTabs(Tabs $tabs): void
    {
        /** @var Tab $tab */
        foreach ($tabs->getTabs() as $tab) {
            $this->tabs->add($tab->getName(), $tab);
        }
    }
}
