<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDietaryProfile extends Model
{
    protected $fillable = ['user_id', 'profile_name', 'is_active'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function dietaryRestrictions()
    {
        return $this->belongsToMany(DietaryRestriction::class, 'user_dietary_restrictions')
                    ->withPivot('severity', 'notes')
                    ->withTimestamps();
    }
}
