<?php

namespace LaravelApiBase\Models;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

        return $limit == 'max' ? $builder->get() : $builder->paginate($limit);
    }

    public function includeContains(Request $request, $builder) {
        $contains = $request->contain ?? $request->include;

        if( !$contains ) {
            return $builder;
        }

        $contains = explode(',', $contains);
        foreach($contains as $contain) {

            $camelVersion = Str::camel(trim($contain));
            if( \method_exists($this, $camelVersion) ) {
                $builder->with($camelVersion);
                continue;
            }

            $snakeCase = Str::snake(trim($contain));
            if( \method_exists($this, $snakeCase) ) {
                $builder->with(trim($snakeCase));
                continue;
            }

            if(strpos($contain, '.') !== false) {
                $parts = array_map(function($part) {
                    return Str::camel($part);
                }, explode('.', $contain));
                $contain = implode(".", $parts);

                $builder->with($contain);
                continue;
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
            if ( Schema::hasColumn($this->table, $this->getCreatedAtColumn()) ) {
                if( strtolower($sort) == 'latest' ) {
                    $builder->latest();
                    continue;
                }

                if( strtolower($sort) == 'oldest' ) {
                    $builder->oldest();
                    continue;
                }
            }


            $sd = explode(":", $sort);
            if ($sd && count($sd) > 0) {
                count($sd) == 2
                    ? $builder->orderBy(trim($sd[0]), trim($sd[1]))
                    : $builder->orderBy(trim($sd[0]), 'asc');
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

        return $builder->first();
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

        // in case the returned model from the search is null, then use the initially found model
        return $builder->first() ?? $dataModel;
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
        $builder = $this->buildSearchParams($request, self::query());
        $builder = $this->applyFilters($request, $builder);
        $builder = $this->includeContains($request, $builder);
        $builder = $this->includeCounts($request, $builder);
        $builder = $this->applySorts($request, $builder);

        return $builder;
    }


    public function count(Request $request)
    {
        return $this->buildSearchParams($request, self::query())->count();
    }

    public function applyFilters(Request $request, $builder) {
        $operators = $this->getQueryOperators();

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

            $builder = $this->applyOperators($builder, $column, $operator, $operator_symbol, $value);
        }

        return $builder;
    }

    public function buildSearchParams(Request $request, $builder) {
        $operators = $this->getQueryOperators();

        foreach ($request->all() as $key => $value) {
            if( !is_numeric($value) && empty($value) ) continue;

            if (in_array($key, $this->searcheableFields())) {
                switch ($key) {
                  default:
                      $builder->where( $this->qualifyColumnInTable($key), '=', $value);
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

                $builder = $this->applyOperators($builder, $column_name, $op_key, $op_type, $value);
            }
        }

        return $builder;
    }

    private function getQueryOperators() {
        return [
            '_not' => '!=',
            '_eq' => '=',
            '_gt' => '>',
            '_lt' => '<',
            '_gte' => '>=',
            '_lte' => '<=',
            '_like' => 'LIKE {x}%',
            '_sw' => 'LIKE {x}%',
            '_ew' => 'LIKE %{x}',
            '_has' => 'LIKE %{x}%',
            '_in' => true,
            '_notIn' => true,
            '_isNull' => true,
            '_isNotNull' => true
        ];
    }

    private function applyOperators($builder, $column_name, $op_key, $op_type, $value) {
        if( !is_numeric($value) && empty($value) ) {
            return $builder;
        }

        $column_name = $this->shouldQualifyColumn($column_name)
            ? $this->qualifyColumn($column_name)
            : $column_name;

        if( $op_key == '_in' ) {
            $builder->whereIn($column_name, explode(',', $value));
        } else if( $op_key == strtolower('_notIn') ) {
            $builder->whereNotIn($column_name, explode(',', $value));
        } else if( $op_key == strtolower('_isNull') ) {
            $builder->whereNull($column_name);
        } else if( $op_key == strtolower('_isNotNull') ) {
            $builder->whereNotNull($column_name);
        } else if( str_contains($op_type, 'LIKE') ) {
            $valData = str_replace("{x}", "{$value}", str_replace("LIKE ", "", $op_type));
            $builder->where($column_name, 'LIKE', $valData);
        } else {
            $builder->where($column_name, $op_type, $value);
        }

        return $builder;
    }

    public function shouldQualifyColumn($column_name) {
        $columns = [
            $this->getCreatedAtColumn() ?? 'created_at',
            $this->getUpdatedAtColumn() ?? 'updated_at',
        ];

        if( \method_exists($this, 'getDeletedAtColumn') ) {
            array_push($columns, $this->getDeletedAtColumn() ?? 'deleted_at');
        }

        return in_array($column_name, $columns);
    }


    /**
     * Qualify the given column name by the model's table.
     *
     * @param  string  $column
     * @return string
     */
    public function qualifyColumnInTable($column, $table = null)
    {
        if (str_contains($column, '.')) {
            return $column;
        }

        if( $table != null ) {
            return "{$table}.{$column}";
        }

        return $this->shouldQualifyColumn($column) ? $this->qualifyColumn($column) : $column;
    }
}
