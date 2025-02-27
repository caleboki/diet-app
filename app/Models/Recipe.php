<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'title', 'api_id', 'instructions', 'prep_time', 'cook_time', 
        'servings', 'calories_per_serving', 'nutrition_data', 'image_url'
    ];
    
    protected $casts = [
        'nutrition_data' => 'array',
        'is_favorite' => 'boolean'
    ];
    
    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
                    ->withPivot('quantity', 'unit', 'preparation', 'is_optional')
                    ->withTimestamps();
    }
    
    public function savedByUsers()
    {
        return $this->hasMany(SavedRecipe::class);
    }
    
    public function modifications()
    {
        return $this->hasMany(RecipeModification::class);
    }
    
    public function groceryListItems()
    {
        return $this->hasMany(GroceryListItem::class);
    }
}
