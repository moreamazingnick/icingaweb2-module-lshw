<?php

namespace Icinga\Module\Lshw\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\Link;

class LshwHardwareTree extends BaseHtmlElement
{

    protected $tag = 'ul';

    protected $defaultAttributes = [
        'class'            => 'tree',
        'data-base-target' => '_next',
    ];

    protected $tree;

    protected $data;

    protected $devices = [];

    protected $parents = [];

    protected $children = [];

    protected $disks = [];

    protected $nics = [];





    public function __construct(string $json_string)
    {
        if(strpos($json_string,"[") !== 0){
            $json_string = '[' . $json_string .']';
        }
        $this->data = json_decode($json_string);
    }


    public function assemble()
    {
        $this->add($this->renderNodes($this->data));
    }
    function convertBytes($bytes,$si=false)
    {
        if($si){
            $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

            $i = 0;
            while ($bytes >= 1000) {
                $bytes /= 1000;
                $i++;
            }
        }else{
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');

            $i = 0;
            while ($bytes >= 1024) {
                $bytes /= 1024;
                $i++;
            }

            return sprintf('%0.2f %s', $bytes, $units[$i]);
        }


        return sprintf('%0.2f %s', $bytes, $units[$i]);
    }
    protected function renderNodes($nodes, $level = 0)
    {
        $result = [];

        foreach ($nodes as $child) {
            $result[] = $this->renderNode($child, $level + 1);
        }

        if ($level === 0) {
            return $result;
        } else {
            return Html::tag('ul', ['class'=>"tree"], $result);
        }
    }

    protected function renderDisk($device,$si){
        $desc="unknown";
        if(isset($device->size)){
            $desc = $this->renderDevice($device);
            $desc .= " / Size: ".$this->convertBytes($device->size,$si);
        }else{
            $desc = $this->renderDevice($device);
        }

        return $desc;
    }
    protected function renderNic($device){
        $desc = $this->renderDevice($device);
        $desc .= " / MAC: ".$device->serial;
        return $desc;
    }

    protected function renderProcessor($device){
        $desc = $this->renderDevice($device);

        if(isset($device->configuration)){
            if(isset($device->configuration->cores)){
                $cores = $device->configuration->cores;
                $desc .= " / $cores cores";
            }

            if(isset($device->configuration->threads)){
                $threads = $device->configuration->threads;
                $desc .= " / $threads threads";
            }

        }
        return $desc;
    }

    protected function renderDevice($device){
        $description = isset($device->description)?$device->description:"";
        $description= " ".$description;
        $product = isset($device->product)?$device->product:"";
        $product= " ".$product;
        $desc = $device->id.":". $description. $product;
        return $desc;
    }

    protected function renderNode($device, $level = 0)
    {
        $key = $device->id;
        $description = isset($device->description)?$device->description:"";
        $description= " ".$description;
        $product = isset($device->product)?$device->product:" Unknown";
        $product= " ".$product;
        $desc = $device->id.":". $description. $product;

        $hasChildren = isset($device->children);

        // TODO: get serious:
        // $isNic = array_key_exists($key, $this->nics

        $class = 'icon-doc-text';


        $li = Html::tag('li');
        if (! $hasChildren) {
            $li->getAttributes()->add('class', 'collapsed');
        }

        if ($hasChildren) {
            $li->add(Html::tag('span', ['class' => 'handle']));
        }

        if ($device->class === "disk" || $device->class === "volume") {
            $class = 'icon-database';
            $li->add(new Link($this->renderDisk($device, true), '#', ['class' => $class,  "style"=>"padding-left: 1.5em"]));
        } elseif ($device->class === "network") {
            $class = 'icon-sitemap';
            $li->add(new Link($this->renderNic($device), '#', ['class' => $class,  "style"=>"padding-left: 1.5em"]));
        } elseif ($device->class === "processor") {
            $class = 'icon-doc-text';
            $li->add(new Link($this->renderProcessor($device), '#', ['class' => $class,  "style"=>"padding-left: 1.5em"]));
        } elseif ($device->class === "memory") {
            $li->add(new Link($this->renderDisk($device,false), '#', ['class' => $class,  "style"=>"padding-left: 1.5em"]));
        } else {
            $li->add(new Link($this->renderDevice($device), '#', ['class' => $class,  "style"=>"padding-left: 1.5em"]));
        }

        if ($hasChildren) {
            $li->add($this->renderNodes($device->children, $level + 1));
        }

        return $li;
    }
}
