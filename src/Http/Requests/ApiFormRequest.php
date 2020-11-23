<?php

namespace LaravelApiBase\Http\Requests;

use Illuminate\Contracts\Validation\ValidatesWhenResolved;

interface ApiFormRequest extends ValidatesWhenResolved {

    /**
     * Validation rules to apply
     */
    public function rules();

    /**
     * Validation error messages
     */
    public function messages();

    /**
     * Implement this method to specify the parameters that should
     * be displayed when the documentation of this Request is generated
     *
     * Method should return an array with a structure similar to the following
     *
     * return [
     *     'field_being_validated' => [
     *         'description' => 'Description of the field',
     *         'example' => 'Example value'
     *     ]
     * ]
     */
    public function bodyParameters();
}
