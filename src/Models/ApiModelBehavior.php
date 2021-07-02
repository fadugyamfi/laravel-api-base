<?php

namespace LaravelApiBase\Models;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Str;

trait ApiModelBehavior
{

    /**
     * Returns a list of fields that can be searched / filtered by. This includes
     * all fillable columns, the primary key column, and the created_at and updated_at columns
     */
    public function searcheableFields() {
        return array_merge($this->fillable, [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn()
        ]);
    }

    /**
     * Retrieves all records based on request data passed in
     */
    public function getAll(Request $request)
    {
        $limit = $request->limit ?? 30;
        $builder =  $this->searchBuilder($request);

        return $builder->paginate($limit);
    }

    public function includeContains(Request $request, $builder) {
        if( $request->contain ) {
            $contains = explode(',', $request->contain);
            foreach($contains as $contain) {
                $camelVersion = Str::camel(trim($contain));
                if( \method_exists($this, $camelVersion) || strpos($contain, '.') !== false ) {
                    if(strpos($contain, '.') !== false) {
                        $parts = explode('.', $contain);
                        $parts = array_map(function($part) {
                            return Str::camel($part);
                        }, $parts);
                        $contain = implode(".", $parts);
                    }

                    $builder->with($camelVersion);
                    continue;
                }

                if( \method_exists($this, $contain) || strpos($contain, '.') !== false ) {
                    $builder->with(trim($contain));
                    continue;
                }
            }
        }

        return $builder;
    }

    public function includeCounts($request, $builder) {
        $count_info = $request->count ?? $request->with_count ?? null;

        if( !$count_info ) {
            return $builder;
        }

        $counters = explode(",", $count_info);

        foreach($counters as $counter) {
            if( \method_exists($this, $counter) ) {
                $builder->withCount($counter);
                continue;
            }

            $camelVersion = Str::camel($counter);
            if( \method_exists($this, $camelVersion) ) {
                $builder->withCount($camelVersion);
                continue;
            }
        }

        return $builder;
    }

    public function applySorts($request, $builder)
    {
        $sorts = $request->sort ? explode(',', $request->sort) : null;

        if ( !$sorts ) {
            return $builder;
        }

        foreach ($sorts as $sort) {
            if( strtolower($sort) == 'latest' ) {
                $builder->latest();
                continue;
            }

            if( strtolower($sort) == 'oldest' ) {
                $builder->oldest();
                continue;
            }

            $sd = explode(":", $sort);
            if ($sd && count($sd) == 2) {
                $builder->orderBy(trim($sd[0]), trim($sd[1]));
            }
        }

        return $builder;
    }

    /**
     * Retrieves a record based on primary key id
     */
    public function getById($id, Request $request)
    {
        $builder = $this->where($this->getQualifiedKeyName(), $id);

        $builder = $this->includeCounts($request, $builder);
        $builder = $this->includeContains($request, $builder);
        $builder = $this->applySorts($request, $builder);

        return $builder->first();
    }

    public function store(Request $request)
    {
        $data = $this->create($request->all());

        $builder = $this->where($this->getQualifiedKeyName(), $data->id);
        $builder = $this->includeContains($request, $builder);
        $builder = $this->includeCounts($request, $builder);

        $dataModel = $builder->first();

        return $dataModel;
    }


    public function modify(Request $request, $id)
    {
        $dataModel = $this->findOrFail($id);

        if (!$dataModel) {
            throw new NotFoundHttpException("Resource not found");
        }

        $dataModel->fill($request->all());
        $dataModel->save();

        $builder = $this->where($this->getQualifiedKeyName(), $id);
        $builder = $this->includeContains($request, $builder);
        $builder = $this->includeCounts($request, $builder);

        $dataModel = $builder->first();

        return $dataModel;
    }

    public function remove($id)
    {
        $record = $this->find($id);

        if( !$record ) {
            return false;
        }

        try {
            return $record->delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * this returns key value pair for select options
     */
    public function getOptions()
    {
        $query = $this->select($this->option_key, $this->option_label)
                ->orderBy($this->option_label, 'asc')
                ->get();

        //convert data to standard object {value:'', label:''}
        $arr = [];
        foreach ($query as $x) {
            if ($x[$this->option_label]) {
                $arr[] = [
                  'value' => $x[$this->option_key],
                  'label' => $x[$this->option_label]
              ];
            }
        }

        return $arr;
    }


    public function search(Request $request)
    {
       $limit = $request->limit ?? 30;
       $builder =  $this->searchBuilder($request);

        return $builder->paginate($limit);
    }

    public function searchBuilder(Request $request) {

        $conditions = [];

        $builder = $this->where($conditions);
        $builder = $this->buildSearchParams($request, $builder);
        $builder = $this->applyFilters($request, $builder);
        $builder = $this->includeContains($request, $builder);
        $builder = $this->includeCounts($request, $builder);
        $builder = $this->applySorts($request, $builder);
        return $builder;
    }


    public function count(Request $request)
    {
        $conditions = [];

        $builder = $this->where($conditions);
        $builder = $this->buildSearchParams($request, $builder);

        return $builder->count();
    }

    public function applyFilters(Request $request, $builder) {
        $operators = [
            'eq' => '=',
            'not' => '!=',
            'gt' => '>',
            'lt' => '<',
            'gte' => '>=',
            'lte' => '<=',
            'like' => 'LIKE',
            'in' => true,
            'notIn' => true,
            'isNull' => true,
            'isNotNull' => true
        ];

        $filters = $request->input('filters', []);

        foreach($filters as $column => $values) {
            if (!in_array($column, $this->searcheableFields())) {
                continue;
            }

            $valueParts = explode(":", $values);
            $operator = "eq";
            $operator_symbol = '=';
            $value = null;

            if(count($valueParts) > 1) {
                $operator = $valueParts[0];
                $operator_symbol = $operators[ $operator ] ?? '=';
                $value = $valueParts[1];
            } else {
                $value = $valueParts[0];
            }

            if( $operator == 'in' ) {
                $builder->whereIn($column, explode(',', $value));
            } else if( $operator == strtolower('notIn') ) {
                $builder->whereNotIn($column, explode(',', $value));
            } else if( $operator == strtolower('isNull') ) {
                $builder->whereNull($column);
            } else if( $operator == strtolower('isNotNull') ) {
                $builder->whereNotNull($column);
            } else if( $operator == 'like' ) {
                $builder->where($column, 'LIKE', "{$value}%");
            } else {
                $builder->where($column, $operator_symbol, $value);
            }
        }

        return $builder;
    }

    public function buildSearchParams(Request $request, $builder) {
        $operators = [
            '_not' => '!=',
            '_gt' => '>',
            '_lt' => '<',
            '_gte' => '>=',
            '_lte' => '<=',
            '_like' => 'LIKE',
            '_in' => true,
            '_notIn' => true,
            '_isNull' => true,
            '_isNotNull' => true
        ];

        foreach ($request->all() as $key => $value) {
            if (in_array($key, $this->searcheableFields())) {
                switch ($key) {
                  default:
                      $builder->where($key, '=', $value);
                      break;
              }
            }

            // apply special operators based on the column name passed
            foreach($operators as $op_key => $op_type) {
                $key = strtolower($key);
                $op_key = strtolower($op_key);
                $column_name = Str::replaceLast($op_key,'',$key);

                $fieldEndsWithOperator = Str::endsWith($key, $op_key);
                $columnIsSearchable = in_array($column_name, $this->searcheableFields());

                if( !$fieldEndsWithOperator || !$columnIsSearchable ) {
                    continue;
                }

                if( $op_key == '_in' ) {
                    $builder->whereIn($column_name, explode(',', $value));
                } else if( $op_key == strtolower('_notIn') ) {
                    $builder->whereNotIn($column_name, explode(',', $value));
                } else if( $op_key == strtolower('_isNull') ) {
                    $builder->whereNull($column_name);
                } else if( $op_key == strtolower('_isNotNull') ) {
                    $builder->whereNotNull($column_name);
                } else if( $op_key == '_like' ) {
                    $builder->where($column_name, 'LIKE', "{$value}%");
                } else {
                    $builder->where($column_name, $op_type, $value);
                }
            }
        }

        return $builder;
    }
}
