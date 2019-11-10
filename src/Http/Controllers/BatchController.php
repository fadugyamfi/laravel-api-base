<?php 

namespace LaravelApiBase\Http\Controllers;

use Illuminate\Http\Request;
use LaravelApiBase\Models\ApiModel;

/**
 * @group Batch Requests
 */
class BatchController extends ApiController {

    public function __construct(ApiModel $model) {
        parent::__construct($model);
    }

    /**
     * Batch Request
     */
    public function index(Request $request) {

        $requests  = $request->all();
        $output = array();

        foreach ($requests as $r) {
            try {
                $r = (object) $r;
                if( is_object($r) && !isset($r->url) ) {
                    continue;
                }
                
                $params = $r->params ?? [];
                $req = Request::create($r->url, $r->method, $params);
                
                // replace the inputs in the current request and set
                $request->replace($req->input());

                // dispatch the new request
                $response = \Route::dispatch($req)->getContent();

                if (isset($r->request_id)) {
                    $output[$r->request_id] = json_decode($response);
                } else {
                    $output[] = json_decode($response);
                }   
            } catch(Exception $e) {
                $res = ['status' => 'error', 'message' => $e->getMessage()];

                if (isset($r->request_id)) {
                    $output[$r->request_id] = $res;
                } else {
                    $output[] = res;
                }
            }
        }  

        return response()->json(array('responses' => $output));
    }
}