<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = ['name', 'api_id', 'description'];
    
    public function dietaryRestrictions()
    {
        return $this->belongsToMany(DietaryRestriction::class, 'ingredient_dietary_restrictions')
                    ->withPivot('is_compliant')
                    ->withTimestamps();
    }
    
    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients')
                    ->withPivot('quantity', 'unit', 'preparation', 'is_optional')
                    ->withTimestamps();
    }
    
    public function groceryListItems()
    {
        return $this->hasMany(GroceryListItem::class);
    }
}
