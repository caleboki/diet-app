<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietaryRestriction extends Model
{
    protected $fillable = ['name', 'description', 'medical_condition_id', 'is_common_allergen'];
    
    public function medicalCondition()
    {
        return $this->belongsTo(MedicalCondition::class);
    }
    
    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'ingredient_dietary_restrictions')
                    ->withPivot('is_compliant')
                    ->withTimestamps();
    }
    
    public function userProfiles()
    {
        return $this->belongsToMany(UserDietaryProfile::class, 'user_dietary_restrictions')
                    ->withPivot('severity', 'notes')
                    ->withTimestamps();
    }
}
