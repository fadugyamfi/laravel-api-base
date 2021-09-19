<?php

namespace LaravelApiBase\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use LaravelApiBase\Models\ApiModel;

/**
 * @group Batch Requests
 */
class BatchController extends Controller {

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

                $response = null;
                $params = $r->params ?? [];

                $req = Request::create($r->url, $r->method, $params, [], [], [], json_encode($params));
                $req->headers->replace($request->headers->all());

                // dispatch the new request
                $response = app()->handle($req)->getContent();

                if (isset($r->request_id)) {
                    $output[$r->request_id] = json_decode($response);
                } else {
                    $output[] = json_decode($response);
                }
            } catch(\Exception $e) {
                $res = ['status' => 'error', 'message' => $e->getMessage()];

                if (isset($r->request_id)) {
                    $output[$r->request_id] = $res;
                } else {
                    $output[] = $res;
                }
            }
        }

        return response()->json(['responses' => $output]);
    }
}
