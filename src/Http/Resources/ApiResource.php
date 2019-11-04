<?php

namespace LaravelApiBase\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class ApiResource extends Resource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $response = parent::toArray($request);

        return $response;
    }
}