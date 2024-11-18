<?php

namespace Icinga\Module\Lshw\Web;

use Icinga\Application\Benchmark;
use Icinga\Module\Director\Db;
use Icinga\Module\Director\Exception\NestingError;
use Icinga\Module\Director\Objects\IcingaObject;
use Icinga\Module\Elasticsearch\Query;
use InvalidArgumentException;
use RuntimeException;

class ObjectTree
{

    protected $parents;

    protected $children;

    protected $rootNodes;

    protected $tree;

    protected $type;

    protected $names;

    public function __construct($type)
    {
        $this->type = $type;

    }

    public function getType()
    {
        return $this->type;
    }


    /**
     * @param $id
     * @param $list
     * @throws NestingError
     */
    protected function assertNotInList($id, &$list)
    {
        if (array_key_exists($id, $list)) {
            $list = array_keys($list);
            array_push($list, $id);

            if (is_int($id)) {
                throw new NestingError(
                    'Loop detected: %s',
                    implode(' -> ', $this->getNamesForIds($list, true))
                );
            } else {
                throw new NestingError(
                    'Loop detected: %s',
                    implode(' -> ', $list)
                );
            }
        }
    }

    protected function getNamesForIds($ids, $ignoreErrors = false)
    {
        $names = [];
        foreach ($ids as $id) {
            $names[] = $this->getNameForId($id, $ignoreErrors);
        }

        return $names;
    }

    protected function getNameForId($id, $ignoreErrors = false)
    {
        if (! array_key_exists($id, $this->names)) {
            if ($ignoreErrors) {
                return "id=$id";
            } else {
                throw new InvalidArgumentException("Got no name for $id");
            }
        }

        return $this->names[$id];
    }




    public function getChildrenById($id)
    {
        $this->requireTree();

        if (array_key_exists($id, $this->children)) {
            return $this->children[$id];
        } else {
            return [];
        }
    }


    public function getTree($parentId = null)
    {
        if ($this->tree === null) {
            $this->prepareTree();
        }

        if ($parentId === null) {
            return $this->returnFullTree();
        } else {
            throw new RuntimeException(
                'Partial tree fetching has not been implemented yet'
            );
            // return $this->partialTree($parentId);
        }
    }

    protected function returnFullTree()
    {
        $result = $this->rootNodes;
        foreach ($result as $id => &$node) {
            $this->addChildrenById($id, $node);
        }

        return $result;
    }

    protected function addChildrenById($pid, array &$base)
    {
        foreach ($this->getChildrenById($pid) as $id => $name) {
            $base['children'][$id] = [
                'name'     => $name,
                'children' => []
            ];
            $this->addChildrenById($id, $base['children'][$id]);
        }
    }

    protected function prepareTree()
    {
        Benchmark::measure(sprintf('Prepare "%s" Template Tree', $this->type));
        $templates = $this->fetchObjects();

        $parents = [];
        $rootNodes = [];
        $children = [];
        $names = [];
        foreach ($templates as $row) {
            $id = (int) $row->id;
            $pid = (int) $row->parent_id;
            $names[$id] = $row->name;
            if (! array_key_exists($id, $parents)) {
                $parents[$id] = [];
            }

            if ($row->parent_id === null) {
                $rootNodes[$id] = [
                    'name' => $row->name,
                    'children' => []
                ];
                continue;
            }

            $names[$pid] = $row->parent_name;
            $parents[$id][$pid] = $row->parent_name;

            if (! array_key_exists($pid, $children)) {
                $children[$pid] = [];
            }

            $children[$pid][$id] = $row->name;
        }

        $this->parents   = $parents;
        $this->children  = $children;
        $this->rootNodes = $rootNodes;
        $this->names = $names;
        Benchmark::measure(sprintf('"%s" Template Tree ready', $this->type));
    }


    protected function requireTree()
    {
        if ($this->parents === null) {
            $this->prepareTree();
        }
    }

    public function fetchObjects()
    {
        $type  = $this->type;
        $table = "icinga_$type";

        $query = $db->select()->from(
            ['o' => $table],
            [
                'id'          => "o.id",
                'name'        => "CONCAT(o.object_name,'_ZONE')",
                'object_type' => 'o.object_type',
                'parent_id'   => 'p.id',
                'parent_name' => 'p.object_name',
            ]

        )->joinLeft(
            ['p' => $table],
            "p.id = o.parent_id",
            []
        )->where('o.object_type', ['object','external_object']);//->order('i.weight');

        $table2="icinga_endpoint";
        $query2 = $db->select()->from(
            ['e' => $table2],
            [
                'id'          => "CONCAT(2147483647,'_ENDPOINT')",
                'name'        => "CONCAT(e.object_name,'_ENDPOINT')",
                'object_type' => 'e.object_type',
                'parent_id'   => 'pa.id',
                'parent_name' => 'pa.object_name',
            ]

        )->joinLeft(
        ['pa' => $table],
        "pa.id = e.zone_id",
        [])->where('e.object_type', ['object','external_object']);

        $query3 = $db->select()->union([$query,$query2])->order('id ASC');

        return $db->fetchAll($query3);
    }
}

/**
 *
SELECT o.id, o.object_name AS name, o.object_type, p.id AS parent_id,
 p.object_name AS parent_name FROM icinga_service AS o
RIGHT JOIN icinga_service_inheritance AS i ON o.id = i.service_id
RIGHT JOIN icinga_service AS p ON p.id = i.parent_service_id
 WHERE (p.object_type = 'template') AND (o.object_type = 'template')
 ORDER BY o.id ASC, i.weight ASC

 */
