<?php

namespace Shexpert\MDatatable;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use MongoDB\Model\BSONDocument;
use Prettus\Repository\Contracts\RepositoryInterface;

class ShexpertDatatable
{
    protected $draw;
    protected $start;
    protected $length;
    protected $columns;
    protected $order;
    protected $search;
    protected $recordsTotal;
    protected $recordsFiltered;
    protected $collection;
    protected $data;
    protected $actualKeys = [];
    protected $selectionKeys = [];
    protected $item = [];
    protected $loopColumn = null;

    public function build()
    {
        /**
         * Send response
         */
        return $this->sendResponse();
    }

    protected function addDatatableFields(array $fields, Collection $collection)
    {
        $this->data = $collection->map(function ($item) use ($fields) {
            $fields = collect($fields)->map(function($field, $key) use ($item, $fields) {
                return $fields[$key]($item);
            });
            return array_merge($item->toArray(), $fields->toArray());
        });
    }

    public function addFields(array $fields)
    {
        $this->addDatatableFields($fields, $this->data);
        return $this;
    }

    public function collection(RepositoryInterface $collection, Request $request, $rawQuery = false)
    {
        $this->initRequest($request);
        $this->collection = $collection;

        if(!$rawQuery) {
            $this->applySearch();
            $this->applyPagination();
            $this->applyOrder();
            $this->setCounts([
                'total'     => $collection->count(),
                'filtered'  => $collection->count()
            ]);
            $this->select([]);
        }
        return $this;
    }

    protected function initRequest(Request $request)
    {
        $this->draw    = (int) $request->get('draw');
        $this->start   = (int) $request->get('start');
        $this->length  = (int) $request->get('length');
        $this->columns = $request->get('columns');
        $this->order   = $request->get('order');
        $this->search  = $request->get('search');
    }

    public function raw(string $method, array $query)
    {
        collect($query)->map(function ($item) {
            collect($item)->map(function ($keyValue, $keyindex) {
                if($keyindex === '$unwind') {
                    $this->loopColumn = str_replace('$', '', $keyValue);
                }
            });
        });

        // Pagination
        $query[] = ['$skip'  => $this->start];
        $query[] = ['$limit' => $this->length];

        // Ordering
        $orders = ['asc' => 1, 'desc' => -1];
        $column = $this->getOrderByColumn();
        $query[] = ['$sort' => [
            $this->loopColumn ? $this->loopColumn: $column['column'].'.'.$column['column'] => $orders[$column['direction']]
        ]];

        $this->rawQuery($method, $query);
        if($this->loopColumn) {
            $this->loopColumn();
        }
        return $this;
    }

    protected function loopColumn()
    {
        $this->data = $this->data->map(function ($item) {
            if(isset($item->{$this->loopColumn}) &&
                ($item->{$this->loopColumn} instanceof BSONDocument)) {
                foreach ((array) $item->{$this->loopColumn}->jsonSerialize() as $key => $value) {
                    $item->$key = $value !== "" ? $value: 'NA';
                }
                unset($item->{$this->loopColumn});
            }
            return $item;
        });
    }

    protected function rawQuery(string $method, array $query)
    {
        $this->data = $this->collection->raw(function($collection) use ($method, $query)
        {
            return $collection->$method($query);
        });
    }

    public function setCounts(array $counts)
    {
        $this->recordsTotal    = isset($counts['total']) ? $counts['total']: 0; // get total no of data;
        $this->recordsFiltered = isset($counts['filtered']) ? $counts['filtered']: 0; // get total no of filtered data;
        return $this;
    }

    public function select(array $data)
    {
        $this->actualKeys = $data;
        $this->getDataKeys();
        $this->setData();
        return $this;
    }

    protected function getDataKeys()
    {
        $this->selectionKeys = collect($this->actualKeys)->map(function ($field) {
            if(strpos($field, '.')) {
                $columns = explode('.', $field);
                return end($columns);
            }
            return $field;
        })->toArray();
        return $this;
    }

    protected function setData()
    {
        $this->data = $this->mapDataWithItem($this->collection->get($this->actualKeys));
        return $this;
    }

    protected function mapDataWithItem(Collection $collection)
    {
        if(empty($this->selectionKeys)) {
            return $collection;
        }
        return $collection->map(function ($item) {
            return $this->iterateThroughItem($item->toArray(), $item);
        });
    }

    protected function iterateThroughItem(array $data, $item)
    {
        foreach ($this->selectionKeys as $index => $selection_key) {
            $key_exists = false;
            foreach ($data as $item_key => $item_value) {
                if(is_array($item_value)) {
                    $this->iterateThroughItem($item_value, $item);
                }
                if ($selection_key === $item_key) {
                    $item->$selection_key = $item_value !== "" ? $item_value: 'NA';
                    $key_exists = true;
                }
            }
            if (! $key_exists) {
                $item->$selection_key = 'NA';
            }
        }
        return $item;
    }

    protected function applyPagination()
    {
        $this->collection = $this->collection->skip($this->start)->take($this->length);
    }

    protected function applySearch()
    {
        $keyword = $this->getSearchValue();
        if ($keyword != null) {
            collect($this->getColumns())->map(function ($column, $key) use ($keyword) {
                if ($key === 0) {
                    $this->collection = $this->collection->where($column, 'like', "%{$keyword}%");
                } else {
                    $this->collection = $this->collection->orWhere($column, 'like', "%{$keyword}%");
                }
            });
        }
    }

    protected function applyOrder()
    {
        $column = $this->getOrderByColumn();
        $this->collection = $this->collection->orderBy($column['column'], $column['direction']);
    }

    protected function getSearchValue()
    {
        return collect($this->search)->first();
    }

    protected function getColumns()
    {
        return collect($this->columns)->pluck('data')->toArray();
    }

    protected function getOrderByColumn()
    {
        $order = collect($this->order)->first();
        return [
            'column'    => $this->columns[$order['column']]['data'],
            'direction' => $order['dir'],
        ];
    }

    protected function sendResponse()
    {
        return response()->json([
            'draw'              => $this->draw,
            'recordsTotal'      => $this->recordsTotal,
            'recordsFiltered'   => $this->recordsFiltered,
            'data'              => $this->data,
            'columns'           => $this->columns,
            'order'             => $this->order,
            'search'            => $this->search,
        ]);
    }
}
