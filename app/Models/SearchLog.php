<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'keyword', 'searched_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
