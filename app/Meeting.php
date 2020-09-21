<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{	
	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'time', 'title', 'description'
    ];

    public function users() {
        return $this->belongsToMany('App\User');
    }
}
