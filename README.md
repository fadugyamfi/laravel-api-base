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
These examples assume you already have your database setup and just need to wire up your Models, Controllers and Routes.

## Making Requests to the API
Once your API endpoints are configured you can make the following requests easily

### Standard RESTful API Requests
```php
GET /todos         // Gets a paginated list to Todo items
GET /todos/{id}    // Get a specific Todo item by ID
POST /todos        // Create a new Todo record 
PUT /todos/{id}    // Update a Todo record 
DELETE /todos/{id} // Delete a Todo record
```

### Querying and Paginating Results
This is where the package shines and enables you to flexible work with your API Endpoints 
```php
GET /todos?limit=5         // Get the first 5 items
GET /todos?page=3&limit=3  // Get page 3 of items with only 3 items
```
### Searching/Filtering Data 
You can search and filter data using any field that is included in the model `$fillables` and apply operators to them to enhance the queries

```php
GET /todos?title=My Todo            // Get all Todos with a title "My Todo" 
GET /todos?description_like=Call    // Returns any todos with the word "call" in the description
GET /todos?id_gt=5&title_like=John  // Gets all todos with an ID greater than 5 and have "John" in the title
GET /todos?id_in=1,3,5              // Gets a specific set of todos by ID
GET /todos?description_isNull       // Get all Todos with a NULL description
```
#### Available Search Operators
- `_like` - Use a `LIKE %{term}%` operation
- `_gt` - Greater Than Operator
- `_gte` - Greater Than or Equal To Operator
- `_lt` - Less Than Operator
- `_lte` - Less Than or Equal To Operatot
- `_in` - IN Operator
- `_notIn` - NOT IN Operator
- `_not` - NOT Operator
- `_isNull` - IS NULL Operator
- `_isNotNull - IS NOT NULL Operator

### Including Associated Models 
You can return any kind of model association that the `with()` operation supports in Laravel Eloquent Models

```php
GET /todos?contain=subtask            // Get all Todos with associated subtasks
GET /todos?contain=subtask.assignee   // Get all Todos with subtasks and subtask assignee
GET /todos?contain=user,subtask       // Get all Todos with associated users and subtasks

// Counting associated models
GET /todos?count=subtask              // Returns a `subtask_count` property in returned results
```

### Sorting Results
You can also sort results by any field when querying
```php
GET /todos?sort=id:desc               // Get results sorted by ID Desc
GET /todos?sort=id:desc,title:asc     // Sort by multiple columns

```

 

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
    
    public function user() {
       return $this->belongsTo(User::class);
    }
    
    public function subtask() {
       return $this->belongsTo(Subtask::class);
    }
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
            'title' => 'required|string',
            'description' => 'required|string'
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

If your Request and Resource classes do not live in the default directories, i.e. `App\Http\Requests` and `App\Http\Resources`, you can override the automatic path resolution by in your Controller `__construct()` function

```php
<?php 

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Http\Requests\Specials\SpecialTodoRequest;
use App\Http\Resources\Specials\SpecialTodoResource;
use LaravelApiBase\Http\Controllers\ApiController;

class TodoController extends ApiController
{
    public function __construct(Todo $todo) {
        parent::__construct($todo);
        
        $this->Request = SpecialTodoRequest::class;
        $this->Resource = SpecialTodoResource::class;
    }
}
```

## Configuring the Routes for your Todo endpoint

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


