<?php

namespace App\Http\Controllers;

use App\Models\MedicalCondition;
use App\Models\DietaryRestriction;
use App\Services\LlmDietaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Get recommended dietary restrictions based on selected medical conditions
     */
    public function getRecommendedRestrictions(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'condition_ids' => 'required|array',
            'condition_ids.*' => 'required|integer|exists:medical_conditions,id',
        ]);

        $conditionIds = $request->input('condition_ids');
        $conditions = MedicalCondition::with('dietaryRestrictions')->findMany($conditionIds);
        
        $llmService = new LlmDietaryService();
        $recommendations = [];
        
        // Process each condition
        foreach ($conditions as $condition) {
            // First, get standard recommendations from relationships
            foreach ($condition->dietaryRestrictions as $restriction) {
                // Only add unique restrictions
                $existingIndex = array_search($restriction->id, array_column($recommendations, 'id'));
                
                if ($existingIndex === false) {
                    $recommendations[] = [
                        'id' => $restriction->id,
                        'name' => $restriction->name,
                        'description' => $restriction->description,
                        'is_common_allergen' => $restriction->is_common_allergen,
                        'recommended_severity' => 'moderate', // Default severity
                        'source_condition' => [
                            'id' => $condition->id,
                            'name' => $condition->name
                        ],
                        'is_ai_generated' => false
                    ];
                }
            }
            
            // Then, check for LLM-enhanced recommendations if we have fewer than 3 standard ones
            // This minimizes LLM API calls by only using them when needed
            $conditionRestrictionCount = count($condition->dietaryRestrictions);
            if ($conditionRestrictionCount < 3) {
                // Get LLM recommendations with caching to minimize API calls
                $llmRecommendations = $llmService->getRecommendationsForCondition($condition);
                
                // Process LLM recommendations
                foreach ($llmRecommendations as $llmRec) {
                    // Check if a similar restriction already exists
                    $isSimilarToExisting = false;
                    foreach ($recommendations as $existingRec) {
                        // Simple similarity check - in production would use better algorithm
                        if (similar_text(strtolower($llmRec['name']), strtolower($existingRec['name'])) > 60) {
                            $isSimilarToExisting = true;
                            break;
                        }
                    }
                    
                    // Only add if not similar to existing restrictions
                    if (!$isSimilarToExisting) {
                        // Find if this recommendation already exists in database
                        $existingDbRestriction = DietaryRestriction::where('name', 'like', '%' . $llmRec['name'] . '%')
                            ->first();
                            
                        if ($existingDbRestriction) {
                            // Use existing database record
                            $recommendations[] = [
                                'id' => $existingDbRestriction->id,
                                'name' => $existingDbRestriction->name,
                                'description' => $existingDbRestriction->description ?? $llmRec['description'],
                                'is_common_allergen' => $existingDbRestriction->is_common_allergen,
                                'recommended_severity' => $llmRec['recommended_severity'],
                                'source_condition' => [
                                    'id' => $condition->id,
                                    'name' => $condition->name
                                ],
                                'is_ai_generated' => true
                            ];
                            
                            // Associate with this condition if not already
                            if (!$condition->dietaryRestrictions->contains($existingDbRestriction->id)) {
                                $condition->dietaryRestrictions()->attach($existingDbRestriction->id);
                            }
                        } else {
                            // This is a novel recommendation - create a temporary ID for frontend
                            // We'll persist it to database only if user selects it
                            $tmpId = 'tmp_' . md5($llmRec['name'] . $condition->id);
                            $recommendations[] = [
                                'id' => $tmpId,
                                'name' => $llmRec['name'],
                                'description' => $llmRec['description'],
                                'is_common_allergen' => false,
                                'recommended_severity' => $llmRec['recommended_severity'],
                                'source_condition' => [
                                    'id' => $condition->id,
                                    'name' => $condition->name
                                ],
                                'is_ai_generated' => true,
                                'is_temporary' => true
                            ];
                        }
                    }
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'recommendations' => $recommendations
        ]);
    }
}
