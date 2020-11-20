<?php

namespace LaravelApiBase\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator as FacadesValidator;

trait ValidationResponseAsJson {

    /**
     * [failedValidation [Overriding the event validator for custom error response]]
     * @param  Validator $validator [description]
     * @return [object][object of various validation errors]
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $validator->errors()->all()
        ], 422));
    }

    /**
     * Creates and returns validator for the supplied data against the rules and messages
     * setup in this Request object
     */
    public static function validate($data): Validator {
        return FacadesValidator::make($data, self::rules(), self::messages());
    }
}
