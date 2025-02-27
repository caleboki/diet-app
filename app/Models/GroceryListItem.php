<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroceryListItem extends Model
{
    protected $fillable = [
        'grocery_list_id', 'ingredient_id', 'name', 'quantity', 
        'unit', 'category', 'is_purchased', 'recipe_id'
    ];
    
    protected $casts = [
        'is_purchased' => 'boolean'
    ];
    
    public function groceryList()
    {
        return $this->belongsTo(GroceryList::class);
    }
    
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
    
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
