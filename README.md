# laravel-api-base
Laravel Package For Easy RESTful API Development

## About

This library enables you to significantly speed up the development of your RESTful API with Laravel by providing you the base 
Controller, Request, Resource and Model with all the CRUD functionality you will need as well as data search and count endpoints.


## Installation

The preferred method of installation is via [Packagist][] and [Composer][]. Run the following command to install the package and add it as a requirement to your project's `composer.json`:

```bash
composer require fadugyamfi/laravel-api-base
```

## Examples

These examples assume you already have your database setup and just need to wire up your Models and Controller. 

### Setting up your Controller

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

