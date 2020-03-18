<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //DB Table to look for data
    protected $table = 'categories';

    //A category can have many posts (RELATION)
    public function posts()
    {
        return $this->hasMany('App\Post');
    }
}
