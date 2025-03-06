<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import axios from 'axios';

// Props and initial data
const props = defineProps({
    medicalConditions: Array,
    commonDietaryRestrictions: Array,
    steps: Object,
    userDietaryProfile: Object
});

// Form state
const currentStep = ref('medical-conditions');
const form = useForm({
    name: '',
    description: '',
    is_active: true,
    medical_conditions: [],
    dietary_restrictions: []
});
const errors = ref({});
const searchQuery = ref('');
const processing = ref(false);
const showTooltip = ref(null);
const customForm = reactive({
    name: '',
    description: ''
});
const customFormMessage = ref('');

// Filtered medical conditions based on search
const filteredMedicalConditions = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.medicalConditions;
    }
    
    const query = searchQuery.value.toLowerCase();
    return props.medicalConditions.filter(condition => 
        condition.name.toLowerCase().includes(query)
    );
});

// Add a custom medical condition to the list
const addCustomCondition = () => {
    axios.post('/medical-conditions', {
        name: customForm.name,
        description: customForm.description
    }).then(response => {
        if (response.data.status === 'duplicate') {
            // Show error message about duplicate condition
            customFormMessage.value = `A similar condition already exists: "${response.data.condition.name}"`;
        } else {
            // Add the custom condition to the list
            const newCondition = response.data.condition;
            
            // Add it to our local list and select it
            props.medicalConditions.push(newCondition);
            
            // Auto-select the new condition with default severity
            const updatedConditions = [...form.medical_conditions];
            updatedConditions.push({
                id: newCondition.id,
                name: newCondition.name,
                severity: 'moderate' // Default severity
            });
            form.medical_conditions = updatedConditions;
            
            // Update recommended restrictions based on the new condition
            updateRecommendedRestrictions();
            
            customFormMessage.value = 'Custom condition added successfully!';
            customForm.name = '';
            customForm.description = '';
        }
    }).catch(error => {
        if (error.response && error.response.data.errors) {
            customFormMessage.value = Object.values(error.response.data.errors).flat().join(' ');
        } else {
            customFormMessage.value = 'An error occurred while creating the custom condition';
        }
    });
};

// Toggle a medical condition selection
const toggleMedicalCondition = (condition) => {
    const index = form.medical_conditions.findIndex(c => c.id === condition.id);
    
    if (index >= 0) {
        // Remove condition - create a new array to ensure reactivity
        const updatedConditions = [...form.medical_conditions];
        updatedConditions.splice(index, 1);
        form.medical_conditions = updatedConditions;
        
        // Update recommended restrictions
        updateRecommendedRestrictions();
    } else {
        // Add condition - create a new array to ensure reactivity
        const updatedConditions = [...form.medical_conditions];
        updatedConditions.push({
            id: condition.id,
            name: condition.name,
            severity: 'moderate' // Default severity
        });
        form.medical_conditions = updatedConditions;
        
        // Update recommended restrictions
        updateRecommendedRestrictions();
    }
};

// Get recommended dietary restrictions based on selected medical conditions
const updateRecommendedRestrictions = () => {
    // Get a copy of all user manually selected restrictions to preserve them
    const userManualSelections = [...form.dietary_restrictions];
    
    // Create a new array to ensure reactivity
    let updatedRestrictions = [];
    
    // Map of medical condition IDs to recommended dietary restriction IDs
    const conditionToRestrictionMap = {
        // Example: Condition ID 1 recommends restrictions 1, 2, 3
        1: [1, 2, 3],
        2: [3, 4],
        3: [2, 5, 6],
        // Add more mappings as needed
    };
    
    // Track recommendations for this selection of conditions
    const recommendedRestrictionIds = new Set();
    
    // Add all recommended restrictions based on selected conditions
    form.medical_conditions.forEach(condition => {
        if (condition.id in conditionToRestrictionMap) {
            conditionToRestrictionMap[condition.id].forEach(restrictionId => {
                recommendedRestrictionIds.add(restrictionId);
            });
        }
    });
    
    // Add all recommended restrictions to the form
    props.commonDietaryRestrictions.forEach(restriction => {
        // If restriction is recommended OR it was manually selected by the user, include it
        if (recommendedRestrictionIds.has(restriction.id) || 
            userManualSelections.some(r => r.id === restriction.id)) {
            
            // Check if this was previously selected (to preserve notes)
            const previousSelection = userManualSelections.find(r => r.id === restriction.id);
            
            // Add to updatedRestrictions, preserving notes if existed
            const severity = deriveSeverity(restriction.id);
            updatedRestrictions.push({
                id: restriction.id,
                name: restriction.name,
                severity: previousSelection ? previousSelection.severity : severity,
                notes: previousSelection ? previousSelection.notes : ''
            });
        }
    });
    
    // Set the form data with the new array to ensure reactivity
    form.dietary_restrictions = updatedRestrictions;
};

// Derive restriction severity based on medical condition severity
const deriveSeverity = (restrictionId) => {
    // Maps between medical conditions and their related restrictions
    const conditionToRestrictionMap = {
        // Example mapping - in production this would be more comprehensive
        // Medical condition ID: { restriction ID: severity mapping function }
        1: { 3: (conditionSeverity) => conditionSeverity }, // Direct mapping
        2: { 4: (conditionSeverity) => conditionSeverity === 'mild' ? 'moderate' : 'severe' }, // Escalated mapping
        // Default behavior for unknown mappings is to match condition severity
    };
    
    // Find medical conditions that might affect this restriction
    let derivedSeverity = 'moderate'; // Default
    
    for (const condition of form.medical_conditions) {
        if (condition.id in conditionToRestrictionMap && 
            restrictionId in conditionToRestrictionMap[condition.id]) {
            
            // Get mapping function for this condition-restriction pair
            const mapFn = conditionToRestrictionMap[condition.id][restrictionId];
            const mappedSeverity = mapFn(condition.severity);
            
            // Take the most severe if multiple conditions affect the same restriction
            if (mappedSeverity === 'severe' || 
               (mappedSeverity === 'moderate' && derivedSeverity !== 'severe')) {
                derivedSeverity = mappedSeverity;
            }
        } else {
            // For unknown mappings, if condition is severe, restrictions should be at least moderate
            if (condition.severity === 'severe' && derivedSeverity !== 'severe') {
                derivedSeverity = 'moderate';
            }
        }
    }
    
    return derivedSeverity;
};

// Update medical condition details
const updateConditionDetails = (conditionId, field, value) => {
    const index = form.medical_conditions.findIndex(c => c.id === conditionId);
    if (index >= 0) {
        // Create a new array to ensure reactivity
        const updatedConditions = [...form.medical_conditions];
        updatedConditions[index] = {
            ...updatedConditions[index],
            [field]: value
        };
        
        // Set the form data with the new array
        form.medical_conditions = updatedConditions;
        
        // If severity was updated, we may need to update recommended restrictions
        if (field === 'severity') {
            updateRecommendedRestrictions();
        }
    }
};

// Check if a medical condition is selected
const isMedicalConditionSelected = (conditionId) => {
    return form.medical_conditions.some(c => c.id === conditionId);
};

// Override the toggleDietaryRestriction function to distinguish manual vs. recommended
const toggleDietaryRestriction = (restriction) => {
    const index = form.dietary_restrictions.findIndex(r => r.id === restriction.id);
    
    if (index >= 0) {
        // Remove restriction - create a new array to ensure reactivity
        const updatedRestrictions = [...form.dietary_restrictions];
        updatedRestrictions.splice(index, 1);
        form.dietary_restrictions = updatedRestrictions;
    } else {
        // Add restriction - create a new array to ensure reactivity
        const updatedRestrictions = [...form.dietary_restrictions];
        updatedRestrictions.push({
            id: restriction.id,
            name: restriction.name,
            severity: 'moderate', // Fixed severity since it's derived from medical conditions
            notes: ''
        });
        
        form.dietary_restrictions = updatedRestrictions;
    }
};

// Check if a dietary restriction is selected
const isDietaryRestrictionSelected = (restrictionId) => {
    return form.dietary_restrictions.some(r => r.id === restrictionId);
};

// Update restriction details
const updateRestrictionDetails = (restrictionId, field, value) => {
    const index = form.dietary_restrictions.findIndex(r => r.id === restrictionId);
    if (index >= 0) {
        // Create a new array to ensure reactivity
        const updatedRestrictions = [...form.dietary_restrictions];
        updatedRestrictions[index] = {
            ...updatedRestrictions[index],
            [field]: value
        };
        
        // Set the form data with the new array
        form.dietary_restrictions = updatedRestrictions;
    }
};

// Add a computed property to check if a restriction is recommended
const isRestrictionRecommended = (restrictionId) => {
    // This would be based on the same logic as updateRecommendedRestrictions
    const conditionToRestrictionMap = {
        1: [1, 2, 3],
        2: [3, 4],
        3: [2, 5, 6],
    };
    
    return form.medical_conditions.some(condition => 
        condition.id in conditionToRestrictionMap && 
        conditionToRestrictionMap[condition.id].includes(restrictionId)
    );
};

// Navigate to next step
const nextStep = () => {
    // Validate current step before proceeding
    const stepValidations = {
        'medical-conditions': () => {
            // Validate that at least one medical condition is selected
            if (form.medical_conditions.length === 0) {
                errors.value = { 'medical_conditions': 'Please select at least one medical condition' };
                return false;
            }
            return true;
        },
        'dietary-restrictions': () => {
            // No validation required for dietary restrictions as they may be optional
            return true;
        },
        'profile-details': () => {
            // Validate profile name
            if (!form.name.trim()) {
                errors.value = { 'name': 'Profile name is required' };
                return false;
            }
            return true;
        },
        'review': () => true // No validation needed for review step
    };

    // Get validation function for current step
    const validateStep = stepValidations[currentStep.value];
    
    // If validation passes, proceed to next step
    if (validateStep && validateStep()) {
        const stepsArray = Object.keys(props.steps);
        const currentIndex = stepsArray.indexOf(currentStep.value);
        
        if (currentIndex < stepsArray.length - 1) {
            // Clear errors before moving to next step
            errors.value = {};
            // Update step
            currentStep.value = stepsArray[currentIndex + 1];
            window.scrollTo(0, 0);
        }
    }
};

// Navigate to previous step
const prevStep = () => {
    const stepsArray = Object.keys(props.steps);
    const currentIndex = stepsArray.indexOf(currentStep.value);
    
    if (currentIndex > 0) {
        currentStep.value = stepsArray[currentIndex - 1];
        window.scrollTo(0, 0);
    }
};

// Submit the form
const submit = () => {
    processing.value = true;
    
    form.post(route('dietary-profile.store'), {
        onSuccess: () => {
            console.log('Form submitted successfully');
            processing.value = false;
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
            processing.value = false;
        },
        preserveScroll: true
    });
};

onMounted(() => {
    // Initialize the form with data if editing
    if (props.userDietaryProfile) {
        // Copy existing profile data to the form
        form.name = props.userDietaryProfile.name;
        form.description = props.userDietaryProfile.description;
        
        // Initialize medical conditions
        form.medical_conditions = props.userDietaryProfile.medical_conditions.map(condition => ({
            id: condition.id,
            name: condition.name,
            severity: condition.pivot.severity
        }));
        
        // Initialize dietary restrictions 
        form.dietary_restrictions = props.userDietaryProfile.dietary_restrictions.map(restriction => ({
            id: restriction.id,
            name: restriction.name,
            severity: restriction.pivot.severity,
            notes: restriction.pivot.notes || ''
        }));
        
        // Update recommended restrictions based on selected medical conditions
        updateRecommendedRestrictions();
    }
});
</script>

<template>
    <AppLayout title="Create Dietary Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Create Dietary Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Steps Progress Bar -->
                <div class="mb-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div 
                                v-for="(label, step, index) in props.steps" 
                                :key="step" 
                                class="flex-1 relative"
                            >
                                <div class="flex items-center">
                                    <div 
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
                                        :class="{
                                            'bg-emerald-600 text-white': currentStep === step || Object.keys(props.steps).indexOf(currentStep) > Object.keys(props.steps).indexOf(step),
                                            'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300': Object.keys(props.steps).indexOf(currentStep) < Object.keys(props.steps).indexOf(step)
                                        }"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    <div v-if="index < Object.keys(props.steps).length - 1" class="flex-1 h-0.5 mx-2"
                                        :class="{
                                            'bg-emerald-600': Object.keys(props.steps).indexOf(currentStep) > index,
                                            'bg-gray-200 dark:bg-gray-700': Object.keys(props.steps).indexOf(currentStep) <= index
                                        }"></div>
                                </div>
                                <div class="text-xs text-center mt-2">{{ label }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Step 1: Medical Conditions -->
                    <div v-if="currentStep === 'medical-conditions'">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                            Select Your Medical Conditions
                        </h3>

                        <div class="mb-6">
                            <InputLabel for="condition-search" value="Search medical conditions" />
                            <TextInput
                                id="condition-search"
                                v-model="searchQuery"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Type to search..."
                            />
                        </div>

                        <div class="space-y-4 max-h-96 overflow-y-auto px-1">
                            <div 
                                v-for="condition in filteredMedicalConditions" 
                                :key="condition.id"
                                class="p-3 border border-gray-200 dark:border-gray-700 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <div class="flex items-center">
                                    <Checkbox
                                        :id="`condition-${condition.id}`"
                                        :checked="isMedicalConditionSelected(condition.id)"
                                        @change="toggleMedicalCondition(condition)"
                                    />
                                    <label 
                                        :for="`condition-${condition.id}`" 
                                        class="ml-2 font-medium"
                                    >
                                        {{ condition.name }}
                                    </label>
                                </div>
                                <p 
                                    class="text-sm ml-7 text-gray-500"
                                >
                                    {{ condition.description }}
                                </p>
                                <div v-if="isMedicalConditionSelected(condition.id)" class="mt-3 ml-7 space-y-3">
                                    <div>
                                        <InputLabel :for="`severity-${condition.id}`" value="Severity" class="text-xs">
                                            <span class="ml-1 cursor-pointer text-gray-500 hover:text-gray-700" 
                                                  @mouseenter="showTooltip = condition.id" 
                                                  @mouseleave="showTooltip = null">
                                                <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </span>
                                        </InputLabel>
                                        
                                        <div v-if="showTooltip === condition.id" class="absolute z-10 bg-white dark:bg-gray-800 p-2 rounded shadow-lg text-xs max-w-xs border border-gray-200 dark:border-gray-700">
                                            <p><strong>Mild:</strong> Occasional symptoms, minimal impact on daily life</p>
                                            <p><strong>Moderate:</strong> Regular symptoms, noticeable impact on wellbeing</p>
                                            <p><strong>Severe:</strong> Significant symptoms, major impact on quality of life</p>
                                        </div>
                                        
                                        <select 
                                            :id="`severity-${condition.id}`" 
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm"
                                            :value="form.medical_conditions.find(c => c.id === condition.id)?.severity"
                                            @change="updateConditionDetails(condition.id, 'severity', $event.target.value)"
                                        >
                                            <option value="mild">Mild (occasional symptoms)</option>
                                            <option value="moderate">Moderate (regular symptoms)</option>
                                            <option value="severe">Severe (significant symptoms)</option>
                                        </select>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Severity will determine dietary restriction levels
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="filteredMedicalConditions.length === 0" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">
                                No medical conditions found matching your search.
                            </p>
                        </div>

                        <div class="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Can't find your condition?
                                </h3>
                            </div>
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                                <form @submit.prevent="addCustomCondition">
                                  <div class="mb-4">
                                    <InputLabel for="custom-name" value="Condition Name" />
                                    <TextInput
                                      id="custom-name"
                                      v-model="customForm.name"
                                      type="text"
                                      class="mt-1 block w-full"
                                      required
                                    />
                                  </div>
                                  
                                  <div class="mb-4">
                                    <InputLabel for="custom-description" value="Description" />
                                    <textarea
                                      id="custom-description"
                                      v-model="customForm.description"
                                      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 focus:ring-offset-2 rounded-md shadow-sm"
                                      required
                                    ></textarea>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                      Please provide details about this condition and how it affects your dietary needs.
                                    </p>
                                  </div>
                                  
                                  <div class="flex items-center justify-end mt-4">
                                    <PrimaryButton class="ml-4" type="submit">
                                      Add Custom Condition
                                    </PrimaryButton>
                                  </div>
                                </form>
                                
                                <div v-if="customFormMessage" class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded">
                                  {{ customFormMessage }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between">
                            <Link 
                                :href="route('dashboard')" 
                                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton @click="nextStep">
                                Next Step
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Step 2: Dietary Restrictions -->
                    <div v-else-if="currentStep === 'dietary-restrictions'">
                        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Dietary Restrictions</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    Based on your medical conditions, we've pre-selected some recommended dietary restrictions.
                                    You can uncheck any you don't want to include, or add additional ones.
                                </p>
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-md mt-4 border border-yellow-200 dark:border-yellow-800">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Important Note</h3>
                                            <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                                <p>
                                                    The severity of each dietary restriction is automatically derived from your medical condition severity.
                                                    <strong>You cannot change restriction severity directly</strong> - update the related medical condition severity instead.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.medical_conditions.length > 0" class="mb-6">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Based on your medical conditions, we recommend:
                                </h4>
                                <!-- Placeholder for recommended restrictions -->
                                <p class="text-gray-500 dark:text-gray-400 text-sm italic mb-4">
                                    In a production app, these would be dynamically generated based on the selected medical conditions.
                                </p>
                                <div class="space-y-3">
                                    <!-- Common dietary restrictions -->
                                    <div 
                                        v-for="restriction in props.commonDietaryRestrictions" 
                                        :key="restriction.id"
                                        class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden"
                                    >
                                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                            <div class="flex items-start">
                                                <div class="flex items-center h-5">
                                                    <input 
                                                        :id="`restriction-${restriction.id}`" 
                                                        type="checkbox" 
                                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:border-gray-700 dark:bg-gray-900"
                                                        :checked="isDietaryRestrictionSelected(restriction.id)"
                                                        @change="toggleDietaryRestriction(restriction)"
                                                    >
                                                </div>
                                                <div class="ml-3 text-sm">
                                                    <label 
                                                        :for="`restriction-${restriction.id}`" 
                                                        class="font-medium text-gray-700 dark:text-gray-300"
                                                    >
                                                        {{ restriction.name }}
                                                    </label>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        {{ restriction.description }}
                                                    </p>
                                                    <div v-if="isDietaryRestrictionSelected(restriction.id)" class="mt-3 space-y-3">
                                                        <div>
                                                            <InputLabel :for="`notes-${restriction.id}`" value="Personal Notes" class="text-xs" />
                                                            <textarea 
                                                                :id="`notes-${restriction.id}`" 
                                                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 focus:ring-offset-2 rounded-md shadow-sm text-sm"
                                                                :value="form.dietary_restrictions.find(r => r.id === restriction.id)?.notes"
                                                                @input="updateRestrictionDetails(restriction.id, 'notes', $event.target.value)"
                                                            />
                                                        </div>
                                                    </div>
                                                    <p v-if="isRestrictionRecommended(restriction.id)" class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">
                                                        Recommended based on your medical conditions
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-6 mb-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-md">
                                <p class="text-yellow-700 dark:text-yellow-300">
                                    No medical conditions selected. You can still add dietary restrictions manually.
                                </p>
                            </div>

                            <div class="mt-8 flex justify-between">
                                <SecondaryButton @click="prevStep">
                                    Previous Step
                                </SecondaryButton>
                                <PrimaryButton @click="nextStep">
                                    Next Step
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Profile Details -->
                    <div v-else-if="currentStep === 'profile-details'">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                            Profile Details
                        </h3>

                        <div class="space-y-6">
                            <div>
                                <InputLabel for="name" value="Profile Name" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="e.g., My Daily Diet, Diabetes Management"
                                />
                            </div>
                            <div class="flex items-center">
                                <Checkbox
                                    id="is_active"
                                    v-model:checked="form.is_active"
                                    class="mr-2"
                                />
                                <InputLabel for="is_active" value="Set as active profile" class="cursor-pointer" />
                                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                    (This will deactivate any other active profiles)
                                </span>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between">
                            <SecondaryButton @click="prevStep">
                                Previous Step
                            </SecondaryButton>
                            <PrimaryButton @click="nextStep">
                                Next Step
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Step 4: Review -->
                    <div v-else-if="currentStep === 'review'">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                            Review Your Dietary Profile
                        </h3>

                        <div class="space-y-6">
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Profile Name
                                </h4>
                                <p class="text-gray-900 dark:text-gray-100">
                                    {{ form.name }}
                                </p>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Status
                                </h4>
                                <p class="text-gray-900 dark:text-gray-100">
                                    <span v-if="form.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                                        Active Profile
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100">
                                        Inactive Profile
                                    </span>
                                </p>
                            </div>

                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Medical Conditions
                                </h4>
                                <div v-if="form.medical_conditions.length > 0" class="flex flex-wrap gap-2">
                                    <span 
                                        v-for="condition in form.medical_conditions" 
                                        :key="condition.id"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100"
                                    >
                                        {{ condition.name }} ({{ condition.severity }})
                                    </span>
                                </div>
                                <p v-else class="text-gray-500 dark:text-gray-400">
                                    No medical conditions selected
                                </p>
                            </div>

                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Dietary Restrictions
                                </h4>
                                <div v-if="form.dietary_restrictions.length > 0" class="space-y-2">
                                    <div 
                                        v-for="restriction in form.dietary_restrictions" 
                                        :key="restriction.id"
                                        class="flex flex-col sm:flex-row sm:items-center justify-between p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-md"
                                    >
                                        <div>
                                            <span class="font-medium text-emerald-700 dark:text-emerald-300">
                                                {{ restriction.name }}
                                            </span>
                                            <span class="ml-2 text-xs text-emerald-600 dark:text-emerald-400">
                                                ({{ restriction.severity }})
                                            </span>
                                        </div>
                                        <div v-if="restriction.notes" class="mt-1 sm:mt-0 text-xs text-gray-500 dark:text-gray-400">
                                            {{ restriction.notes }}
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-gray-500 dark:text-gray-400">
                                    No dietary restrictions selected
                                </p>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between">
                            <SecondaryButton @click="prevStep">
                                Previous Step
                            </SecondaryButton>
                            <PrimaryButton :disabled="processing" @click="submit">
                                <span v-if="processing" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing...
                                </span>
                                <span v-else>Create Profile</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
