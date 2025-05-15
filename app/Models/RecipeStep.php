<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeStep extends Model
{
    protected $fillable = [
        'recipe_id',
        'step_number',
        'description',
        'image',
    ];

    // 🔗 Liên kết với công thức
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
