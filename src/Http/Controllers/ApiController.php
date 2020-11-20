<?php

namespace LaravelApiBase\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use LaravelApiBase\Models\ApiModelInterface;

abstract class ApiController extends BaseController
{

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ApiControllerBehavior;

    public function __construct(ApiModelInterface $model, String $resource = null)
    {
        $this->Model = $model;

        $this->setResource($resource);
    }
}
