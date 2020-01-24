# laravel-api-base
Laravel Package For Easy RESTful API Development

## About

This library enables you to significantly speed up the development of your RESTful API with Laravel by providing you the base 
Controller, Request, Resource and Model with all the CRUD functionality you will need as well as data search and count endpoints.


# Installation

The preferred method of installation is via [Packagist][] and [Composer][]. Run the following command to install the package and add it as a requirement to your project's `composer.json`:

```bash
composer require fadugyamfi/laravel-api-base
```

Latest Laravel versions have auto dicovery and automatically add service providers, however if you're using Laravel 5.4.x and below, remember to add it to `providers` array at `/app/config/app.php`:

```php
// ...
LaravelApiBase\Providers\LaravelApiBaseProvider::class,
```

Lastly, and most importantly, this package provides an updated `Router`, i.e. `LaravelApiBase\Services\ApiRouter` that you must configure in your `app/bootstrap/app.php` file to get the full benefits of the api. You need to add the following code in the file.

```php
/**
 * Important change to ensure the right router version is used with the Laravel API Base package
 */
$app->singleton('router', LaravelApiBase\Services\ApiRouter::class);

```


# Using the Library

These examples assume you already have your database setup and just need to wire up your Models and Controller. 

## Setting up your Model

To access the data you will need a model.

```php
<?php

namespace App\Models;

use LaravelApiBase\Models\ApiModel;

class Todo extends ApiModel
{

    protected $table = 'todos';

    protected $fillable = ['title', 'description'];
}
```

## Setting up Requests for Input Validations

When creating or updating a record, we often need to validate the inputs. Creating a Request object will enable this to
happen behind the scenes.

```php
<?php

namespace App\Http\Requests;

use LaravelApiBase\Http\Requests\ApiRequest;

class TodoRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title'=>'required|string',
            'description'=>'required|string'
        ];
    }

}

```

## Setting up a JSONResource to format your responses

You can customize the response that comes back to your users by creating a subclass of `LaravelApiBase\Http\Resources\APIResource`

```php

<?php

namespace App\Http\Resources;

use LaravelApiBase\Http\Resources\ApiResource;

class TodoResource extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $url = 'https://via.placeholder.com/150';

        return array_merge(parent::toArray($request), [
            'url' => $url
        ]);
    }
}
```

## Setting up your Controller

By simply specifying the model to use, the controller will infer any resources or request objects needed and use them for 
all your restful endpoints

```php
<?php 

namespace App\Http\Controllers;

use App\Models\Todo;
use LaravelApiBase\Http\Controllers\ApiController;

class TodoController extends ApiController
{
    public function __construct(Todo $todo) {
        parent::__construct($todo);
    }
}
```

## Adding a Route for your Todo endpoint

Once you have everything setup, you can now add a route that will make your resource available. You should potentially add these to
the `routes/api.php` file.

```php

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// adds all the basic endpoints for GET, POST, PUT, DELETE as well as /search and /count
Route::apiResource('todos', 'TodoController'); 
```

### Submitting bugs and feature requests

Bugs and feature request are tracked on [GitHub](https://github.com/fadugyamfi/laravel-api-base/issues)

### Author

Francis Adu-Gyamfi - <https://www.linkedin.com/in/francis-adu-gyamfi-3b782716/><br />
See also the list of [contributors](https://github.com/fadugyamfi/laravel-api-base/contributors) which participated in this project.

### License

Laravel API Base is licensed under the MIT License - see the `LICENSE` file for details

### Acknowledgements

This library was possible due to the team of developers at Matrix Designs who inspired its creation.


