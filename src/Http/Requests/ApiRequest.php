<?php

namespace LaravelApiBase\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiRequest extends FormRequest implements ApiFormRequest
{
    use ValidationResponseAsJson;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }


    public function rules() {
        return [];
    }

    public function messages() {
        return [];
    }

    public function bodyParameters(): array {
        return [];
    }
}
