<?php

namespace App\Services;

use App\Models\MedicalCondition;
use App\Models\DietaryRestriction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlmDietaryService
{
    /**
     * Get dietary recommendations for a medical condition using LLM
     * with response caching to minimize API calls
     */
    public function getRecommendationsForCondition(MedicalCondition $condition)
    {
        // Cache key based on condition ID and name
        $cacheKey = 'llm_recommendations_' . $condition->id . '_' . md5($condition->name);
        
        // Check if recommendations are cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        // If not in cache, call the LLM API
        $recommendations = $this->callLlmApi($condition);
        
        // Cache the response for future use (1 week expiration)
        Cache::put($cacheKey, $recommendations, now()->addWeek());
        
        return $recommendations;
    }
    
    /**
     * Call the LLM API to get dietary restrictions for a medical condition
     */
    private function callLlmApi(MedicalCondition $condition)
    {
        try {
            // This is a placeholder for the actual LLM API call
            // In a real implementation, you would call an LLM API like OpenAI
            
            // Example API call structure (commented out for now):
            /*
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system', 
                        'content' => 'You are a nutritional expert assistant. Generate dietary restrictions for medical conditions in JSON format.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Generate dietary restrictions for someone with {$condition->name}. Include restriction name, description, and recommended severity (mild, moderate, severe)."
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 1000,
            ]);
            
            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return json_decode($content, true);
            }
            */
            
            // DEMO IMPLEMENTATION: For now, return mock data based on condition
            return $this->getMockRecommendations($condition);
            
        } catch (\Exception $e) {
            Log::error('LLM API Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate mock recommendations for demo purposes
     * In production, this would be replaced by actual LLM responses
     */
    private function getMockRecommendations(MedicalCondition $condition)
    {
        $conditionName = strtolower($condition->name);
        
        $commonRecommendations = [
            [
                'name' => 'Low sodium diet',
                'description' => 'Reduce sodium intake to less than 2,300mg per day',
                'recommended_severity' => 'moderate'
            ],
            [
                'name' => 'Processed sugar restriction',
                'description' => 'Limit intake of foods with added sugars',
                'recommended_severity' => 'mild'
            ]
        ];
        
        // Condition-specific mock recommendations
        if (str_contains($conditionName, 'diabet')) {
            return array_merge($commonRecommendations, [
                [
                    'name' => 'Low glycemic index diet',
                    'description' => 'Focus on foods that have a low impact on blood sugar levels',
                    'recommended_severity' => 'severe'
                ],
                [
                    'name' => 'Carbohydrate restriction',
                    'description' => 'Limit intake of simple carbohydrates and monitor total carbs',
                    'recommended_severity' => 'moderate'
                ]
            ]);
        } elseif (str_contains($conditionName, 'celiac') || str_contains($conditionName, 'gluten')) {
            return array_merge($commonRecommendations, [
                [
                    'name' => 'Gluten-free diet',
                    'description' => 'Avoid all foods containing gluten, including wheat, barley, and rye',
                    'recommended_severity' => 'severe'
                ]
            ]);
        } elseif (str_contains($conditionName, 'hypertension') || str_contains($conditionName, 'blood pressure')) {
            return array_merge($commonRecommendations, [
                [
                    'name' => 'DASH diet',
                    'description' => 'Follow the Dietary Approaches to Stop Hypertension eating plan',
                    'recommended_severity' => 'moderate'
                ],
                [
                    'name' => 'Alcohol restriction',
                    'description' => 'Limit alcohol consumption to moderate levels',
                    'recommended_severity' => 'moderate'
                ]
            ]);
        }
        
        // Default recommendations if no specific match
        return $commonRecommendations;
    }
    
    /**
     * Store LLM-generated recommendations in the database
     * This converts LLM recommendations into actual DietaryRestriction models
     */
    public function storeRecommendations(MedicalCondition $condition, array $recommendations)
    {
        foreach ($recommendations as $rec) {
            // Check if a similar restriction already exists
            $existingRestriction = DietaryRestriction::where('name', 'like', '%' . $rec['name'] . '%')
                ->first();
                
            if ($existingRestriction) {
                // If exists, associate with the condition if not already
                if ($existingRestriction->medical_condition_id !== $condition->id) {
                    $existingRestriction->update([
                        'medical_condition_id' => $condition->id
                    ]);
                }
            } else {
                // Create new restriction
                DietaryRestriction::create([
                    'name' => $rec['name'],
                    'description' => $rec['description'],
                    'medical_condition_id' => $condition->id,
                    'is_common_allergen' => false,
                    'is_ai_suggested' => true
                ]);
            }
        }
    }
}
