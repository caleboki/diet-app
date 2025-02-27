<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeModification extends Model
{
    protected $fillable = [
        'recipe_id', 'user_id', 'original_content', 
        'modified_content', 'modification_reason', 'llm_response'
    ];
    
    protected $casts = [
        'llm_response' => 'array'
    ];
    
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
