<?php

namespace LaravelApiBase\Http\Controllers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use LaravelApiBase\Http\Requests\ApiRequest;
use LaravelApiBase\Models\ApiModelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ApiControllerBehavior
{

    /** @var ApiModel */
    public $Model;

    /** @var Resource */
    public $Resource;

    /** @var Collection */
    public $Collection;

    /** @var Request */
    public $Request;

    public function setResource($resource = null)
    {
        $packageResNS = "\\LaravelApiBase\\Http\\Resources";
        $packageReqNS = "\\LaravelApiBase\\Http\\Requests";

        $resourceNS = "\\App\\Http\\Resources";
        $requestNS = "\\App\\Http\\Requests";
        $modelClassName = str_replace('App\\Models\\', '', get_class($this->Model));

        if (!$this->Resource) {

            if ($resource) {
                if (strpos($resource, $resourceNS) === false) {
                    $this->Resource = $resourceNS . "\\{$resource}";
                } else {
                    $this->Resource = $resource;
                }
            } else {
                $this->Resource = $resourceNS . "\\" . $modelClassName . 'Resource';
            }

            try {
                if (!class_exists($this->Resource)) {
                    throw new \Exception('Missing resource');
                }
            } catch (\Error | \Exception $e) {
                $this->Resource = $packageResNS . "\\ApiResource";
            }

        }

        if (!$this->Request) {
            $this->Request = $requestNS . "\\" . $modelClassName . 'Request';

            try {
                if (!class_exists($this->Request)) {
                    throw new \Exception('Missing request');
                }
            } catch (\Error | \Exception $e) {
                $this->Request = $packageReqNS . "\\ApiRequest";
            }
        }
    }

    public function setApiFormRequest(string $request)
    {
        $this->Request = $request;
    }

    public function setApiResource(string $resource)
    {
        $this->Resource = $resource;
    }

    public function setApiModel(ApiModelInterface $model)
    {
        $this->Model = $model;
        $this->setResource();
    }

    /**
     * Get All
     *
     * Returns a list of items in this resource and allows filtering the data based on fields in the database
     *
     * Options for searching / filtering
     * - By field name: e.g. `?name=John` - Specific search
     * - By field name with `LIKE` operator: e.g. `?name_like=John` - Fuzzy search
     * - By field name with `!=` operator: e.g. `?age_not=5`
     * - By field name with `>` or `<` operator: e.g. `?age_gt=5` or `?age_lt=10`
     * - By field name with `>=` or `<=` operator: e.g. `?age_gte=5` or `?age_lte=10`
     * - By field name with `IN` or `NOT IN` operator: e.g. `?id_in=1,3,5` or `?id_notIn=2,4`
     * - By field name with `NULL` or `NOT NULL` operator: e.g. `?email_isNull` or `?email_isNotNull`
     *
     * @queryParam limit Total items to return e.g. `?limit=15`. Example: 3
     * @queryParam page Page of items to return e.g. `?page=1`. Example: 1
     * @queryParam sort Sorting options e.g. `?sort=surname:asc,othernames:asc`. Example: id:asc
     * @queryParam count Count related models. Alternatively `with_count` e.g. `?count=student,student_program`. No-example
     * @queryParam contain Contain data from related model e.g. `?contain=program,currency`. No-example
     * @queryParam fieldName Pass any field and value to filter results e.g. `name=John&email=any@aol.com`. No-example
     *
     * @authenticated
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $dataModel = $this->Model->getAll($request);
        return $this->Resource::collection($dataModel);
    }

    /**
     * Create Resource
     *
     * Create a new record of this resource in the database. You can return related data or counts of related data
     * in the response using the `count` and `contain` query params
     *
     * @queryParam count Count related models. Alternatively `with_count` e.g. `?count=student,student_program`. No-example
     * @queryParam contain Contain data from related model e.g. `?contain=program,currency`. No-example
     *
     * @authenticated
     * @param  \LaravelApiBase\Http\Requests\ApiRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(ApiRequest $request)
    {
        try {
            if (class_exists($this->Request)) {
                $formRequest = new $this->Request($request->all());
                $validator = Validator::make($request->all(), $formRequest->rules(), $formRequest->messages());

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $validator->errors()->all(),
                    ], 400);
                }
            }

            $dataModel = $this->Model->store($request);
            return new $this->Resource($dataModel);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View Resource
     *
     * Returns information about a specific record in this resource. You can return related data or counts of related data
     * in the response using the `count` and `contain` query params
     *
     * @queryParam count Count related models. Alternatively `with_count` e.g. `?count=student,student_program`. No-example
     * @queryParam contain Contain data from related model e.g. `?contain=program,currency`. No-example
     *
     * @authenticated
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $dataModel = $this->Model->getById($id, $request);

        if ($dataModel) {
            return new $this->Resource($dataModel);
        }

        return response()->json([
            'status' => 'failed',
            'message' => 'Resource not found',
        ], 404);
    }

    /**
     * Update Resource
     *
     * Updates the data of the record with the specified `id`. You can return related data or counts of related data
     * in the response using the `count` and `contain` query params
     *
     * @queryParam count Count related models. Alternatively `with_count` e.g. `?count=student,student_program`. No-example
     * @queryParam contain Contain data from related model e.g. `?contain=program,currency`. No-example
     *
     * @authenticated
     * @param  \Illuminate\Foundation\Http\FormRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ApiRequest $request, $id)
    {
        try {

            if (class_exists($this->Request)) {
                $formRequest = new $this->Request($request->all());
                $validator = Validator::make($request->all(), $formRequest->rules(), $formRequest->messages());

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $validator->errors()->all(),
                    ], 400);
                }
            }

            $dataModel = $this->Model->modify($request, $id);
            return new $this->Resource($dataModel);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Resource not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getTrace(),
            ], 500);
        }
    }

    /**
     * Delete Resource
     *
     * Deletes the record with the specified `id`
     *
     * @authenticated
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $dataModel = $this->Model->find($id);

        if ($dataModel) {
            $dataModel->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Resource deleted',
                'data' => $dataModel,
            ]);
        }

        return response()->json([
            'status' => 'failed',
            'message' => 'Resource not found',
        ], 404);
    }

    /**
     * Search Resources
     *
     * Allows searching for data in this resource using multiple options.
     *
     * Options for searching
     * - By field name: e.g. `?name=John` - Specific search
     * - By field name with `LIKE` operator: e.g. `?name_like=John` - Fuzzy search
     * - By field name with `!=` operator: e.g. `?age_not=5`
     * - By field name with `>` or `<` operator: e.g. `?age_gt=5` or `?age_lt=10`
     * - By field name with `>=` or `<=` operator: e.g. `?age_gte=5` or `?age_lte=10`
     * - By field name with `IN` or `NOT IN` operator: e.g. `?id_in=1,3,5` or `?id_notIn=2,4`
     * - By field name with `NULL` or `NOT NULL` operator: e.g. `?email_isNull` or `?email_isNotNull`
     *
     * @queryParam limit Total items to return e.g. `?limit=15`. Example: 3
     * @queryParam page Page of items to return e.g. `?page=1`. Example: 1
     * @queryParam sort Sorting options e.g. `?sort=surname:asc,othernames:asc`. Example: id:asc
     * @queryParam count Count related models. Alternatively `with_count` e.g. `?count=student,student_program`. No-example
     * @queryParam contain Contain data from related model e.g. `?contain=program,currency`. No-example
     * @queryParam fieldName Pass any field and value to search by e.g. `name=John&email=any@aol.com`. Search logic may use LIKE or `=` depending on field
     *
     * @authenticated
     */
    public function search(Request $request)
    {
        $results = $this->Model->search($request);

        return $this->Resource::collection($results);
    }

    /**
     * Count Resources
     *
     * Returns a simple count of data in this resource
     *
     * @queryParam fieldName Pass any field and value to search by e.g. `name=John&email=any@aol.com`. Search logic may use LIKE or `=` depending on field. No-example
     *
     * @authenticated
     */
    public function count(Request $request)
    {
        $results = $this->Model->count($request);

        return response()->json(['count' => $results]);
    }
}
