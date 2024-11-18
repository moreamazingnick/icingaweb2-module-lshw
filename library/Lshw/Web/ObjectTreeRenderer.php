<?php

namespace Icinga\Module\Lshw\Web;

use Icinga\Module\Director\Db;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use gipfl\IcingaWeb2\Link;
use gipfl\Translation\TranslationHelper;
use gipfl\IcingaWeb2\Widget\ControlsAndContent;

class ObjectTreeRenderer extends BaseHtmlElement
{
    use TranslationHelper;

    protected $tag = 'ul';

    protected $defaultAttributes = [
        'class'            => 'tree',
        'data-base-target' => '_next',
    ];

    protected $tree;
    protected $for;

    public function __construct(ObjectTree $tree,$for)
    {
        $this->tree = $tree;
        $this->for = $for;

    }

    public static function showType($type, ControlsAndContent $controller, Db $db)
    {
       /*
        $controller->content()->add(
            new static(new ObjectTree($type, $db))
        );
       */
    }

    public function renderContent()
    {
        $tree = $this->tree->getTree();

        $this->add(
            $this->dumpTree(
                array(
                    'name' => $this->translate($this->for),
                    'children' => $tree
                )
            )
        );

        return parent::renderContent();
    }

    protected function dumpTree($tree, $level = 0)
    {


        if ($level === 0) {
            $type = $this->tree->getType();
            $name=$tree['name'];
        }else{
            $tmp =explode("_",$tree['name']);
            $type= array_pop($tmp);
            $name = implode("_",$tmp);
            $type = strtolower($type);
        }


        $hasChildren = (!empty($tree['children']));



        $li = Html::tag('li');
        if (! $hasChildren) {
            $li->getAttributes()->add('class', 'collapsed');
        }

        if ($hasChildren) {
            $li->add(Html::tag('span', ['class' => 'handle']));
        }

        if ($level === 0) {
            $li->add(Html::tag('a', [
                'name'  => $name,
                'class' => 'icon-globe'
            ], $name));
        } else if($type == "endpoint"){
            $li->add(Link::create(
                $name,
                "director/${type}",
                array('name' => $name),
                array('class' => 'icon-' ."host")
            ));
        } else if($type == "zone"){
            $li->add(Link::create(
                $name,
                "director/${type}",
                array('name' => $name),
                array('class' => 'icon-' ."globe")
            ));
        }else {
            $li->add(Link::create(
                $name,
                "director/${type}",
                array('name' => $name),
                array('class' => 'icon-' ."bug")
            ));
        }

        if ($hasChildren) {
            $li->add(
                $ul = Html::tag('ul')
            );
            foreach ($tree['children'] as $child) {
                if ($level > 2){
                    break;
                }
                $ul->add($this->dumpTree($child, $level + 1));

            }
        }

        return $li;
    }
}
