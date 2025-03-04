<?php

namespace App\Http\Controllers;

use App\Models\MedicalCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MedicalConditionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = $request->input('query');
        $userId = Auth::id();
        
        $conditions = MedicalCondition::availableTo($userId)
            ->when($query, function ($q) use ($query) {
                return $q->where('name', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->get()
            ->map(function ($condition) {
                // Add a flag to indicate if this is a custom condition
                return array_merge($condition->toArray(), [
                    'is_custom' => !is_null($condition->user_id),
                ]);
            });
            
        return response()->json($conditions);
    }

    /**
     * Store a newly created medical condition.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);
        
        // Check if a similar condition already exists (fuzzy matching)
        $similarCondition = MedicalCondition::availableTo(Auth::id())
            ->where('name', 'like', '%' . $validated['name'] . '%')
            ->first();
            
        if ($similarCondition) {
            return response()->json([
                'message' => 'A similar condition already exists',
                'condition' => array_merge($similarCondition->toArray(), [
                    'is_custom' => !is_null($similarCondition->user_id),
                ]),
                'status' => 'duplicate'
            ]);
        }
        
        // Create new custom condition
        $condition = new MedicalCondition();
        $condition->name = $validated['name'];
        $condition->description = $validated['description'];
        $condition->user_id = Auth::id();
        $condition->is_verified = false;
        $condition->save();
        
        // Log the creation of a custom condition
        Log::info('Custom medical condition created', [
            'condition_id' => $condition->id,
            'condition_name' => $condition->name,
            'user_id' => Auth::id()
        ]);
        
        return response()->json([
            'message' => 'Medical condition created successfully',
            'condition' => array_merge($condition->toArray(), [
                'is_custom' => true,
            ]),
            'status' => 'created'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicalCondition $medicalCondition)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MedicalCondition $medicalCondition)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MedicalCondition $medicalCondition)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicalCondition $medicalCondition)
    {
        //
    }
    
    /**
     * Get related dietary restrictions for a medical condition.
     */
    public function restrictions(MedicalCondition $medicalCondition)
    {
        $restrictions = $medicalCondition->dietaryRestrictions;
        
        // If this is a custom condition or has no specific restrictions,
        // we could use AI to suggest restrictions based on the name/description
        if ($restrictions->isEmpty() && (!is_null($medicalCondition->user_id) || $medicalCondition->is_verified === false)) {
            // This would be where an AI API call would happen
            // For now, we'll return empty, but in a full implementation
            // you would add suggested restrictions here
            
            // Placeholder for AI-generated suggestions
            $aiSuggestedRestrictions = [];
            
            return response()->json([
                'restrictions' => $aiSuggestedRestrictions,
                'is_ai_suggested' => true
            ]);
        }
        
        return response()->json([
            'restrictions' => $restrictions,
            'is_ai_suggested' => false
        ]);
    }
}
