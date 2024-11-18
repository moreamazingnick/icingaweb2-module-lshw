<?php
/** @var $this \Icinga\Application\Modules\Module */


$this->provideHook('Icingadb/PluginOutput');
$this->provideHook('Monitoring/PluginOutput');
$this->provideHook('Icingadb/ServiceDetailExtension');
$this->provideHook('Monitoring/DetailviewExtension');