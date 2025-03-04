<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalCondition extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'description', 'user_id', 'is_verified'];
    
    protected $casts = [
        'is_verified' => 'boolean',
    ];

    /**
     * Get the user that created this medical condition.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
    
    /**
     * Scope a query to only include global conditions (not user-specific).
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('user_id');
    }
    
    /**
     * Scope a query to only include conditions for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    /**
     * Scope a query to include both global conditions and conditions for a specific user.
     */
    public function scopeAvailableTo($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->whereNull('user_id')
              ->orWhere('user_id', $userId);
        });
    }
}
