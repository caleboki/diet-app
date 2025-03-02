<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalCondition extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'description'];

    /**
     * Get the dietary restrictions related to this medical condition.
     */
    public function dietaryRestrictions(): HasMany
    {
        return $this->hasMany(DietaryRestriction::class);
    }
    
    /**
     * Get the dietary profiles that have this medical condition.
     */
    public function dietaryProfiles(): BelongsToMany
    {
        return $this->belongsToMany(UserDietaryProfile::class, 'user_medical_conditions')
                    ->withPivot('severity')
                    ->withTimestamps();
    }
}
