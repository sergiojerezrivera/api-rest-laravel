<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    //DB table to look for data
    protected $table = 'posts';

    //Many posts created by a user (RELATION)
    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }

    //Many posts added to 1 category at the time (RELATION)
    public function category() {
        return $this->belongsTo('App\Category', 'category_id');
    }

    protected $fillable = [
        'title', 'content', 'category_id'
    ];
}
