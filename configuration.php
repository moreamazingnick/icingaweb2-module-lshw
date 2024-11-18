<?php
$this->providePermission('config/lshw', $this->translate('allow access to lshw configuration'));

$this->provideConfigTab('config/director', array(
    'title' => $this->translate('Director Configuration'),
    'label' => $this->translate('Director Configuration'),
    'url' => 'director'
));

?>