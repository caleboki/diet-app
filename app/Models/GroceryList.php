<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroceryList extends Model
{
    protected $fillable = ['user_id', 'name', 'shopping_date', 'is_active'];
    
    protected $casts = [
        'shopping_date' => 'date',
        'is_active' => 'boolean'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function items()
    {
        return $this->hasMany(GroceryListItem::class);
    }
}
