<?php

namespace LaravelApiBase\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Provides a base class to extend functionality from if users prefer Inheritance
 * over Composition using the ApiModelBehavior and Interace
 */
class ApiModel extends Model implements ApiModelInterface
{
    use ApiModelBehavior;
}
