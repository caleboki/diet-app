<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, defineProps } from 'vue';

const props = defineProps({
    activeProfile: Object,
    stats: Object,
    recentProfiles: Array,
    totalProfilesCount: Number,
    commonRestrictions: Array,
    recommendedRecipes: Array
});

const hasActiveProfile = computed(() => !!props.activeProfile);
const hasMedicalConditions = computed(() => hasActiveProfile.value && props.activeProfile.medical_conditions?.length > 0);
const hasRestrictions = computed(() => hasActiveProfile.value && props.activeProfile.dietary_restrictions?.length > 0);

const getSeverityColor = (severity) => {
    switch(severity) {
        case 'mild': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
        case 'moderate': return 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100';
        case 'severe': return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100';
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    }).format(date);
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Your Personalized Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Welcome and Profile Summary -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                    <div v-if="hasActiveProfile" class="flex flex-col md:flex-row items-start md:items-center justify-between">
                        <div class="flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                Welcome to Your Dietary Profile: {{ props.activeProfile.profile_name }}
                            </h3>
                            <p v-if="props.activeProfile.description" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ props.activeProfile.description }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2 max-w-xl">
                                <span 
                                    v-for="restriction in props.activeProfile.dietary_restrictions" 
                                    :key="restriction.id"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100"
                                >
                                    {{ restriction.name }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-4 md:mt-0 flex space-x-3">
                            <Link 
                                :href="route('dietary-profile.show', props.activeProfile.id)" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                            >
                                View Profile
                            </Link>
                            <Link 
                                :href="route('dietary-profile.edit', props.activeProfile.id)" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                            >
                                Edit Profile
                            </Link>
                        </div>
                    </div>

                    <div v-else class="flex flex-col md:flex-row items-start md:items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Set Up Your Dietary Profile
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Create a dietary profile to get personalized health insights and recipe recommendations based on your health conditions and preferences.
                            </p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <Link 
                                :href="route('dietary-profile.create')" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                            >
                                Create Profile
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-if="hasActiveProfile" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Statistics Card -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Your Health Summary
                        </h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Total Medical Conditions:</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ props.stats.totalMedicalConditions }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Total Dietary Restrictions:</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ props.stats.totalRestrictions }}</span>
                            </div>
                            <div class="pt-2">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Conditions by Severity:</h4>
                                <div class="flex gap-2">
                                    <div v-for="(count, severity) in props.stats.conditionsBySeverity" :key="severity" 
                                        class="flex-1 rounded-md p-2 text-center" 
                                        :class="getSeverityColor(severity)">
                                        <div class="font-bold">{{ count }}</div>
                                        <div class="text-xs capitalize">{{ severity }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Medical Conditions List -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Your Medical Conditions
                        </h3>
                        <div v-if="hasMedicalConditions" class="space-y-3">
                            <div v-for="condition in props.activeProfile.medical_conditions" :key="condition.id" 
                                class="p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ condition.name }}</span>
                                    <div class="flex items-center space-x-2">
                                        <span 
                                            v-if="condition.is_custom" 
                                            class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100"
                                        >
                                            Custom
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-xs font-medium" :class="getSeverityColor(condition.pivot.severity)">
                                            {{ condition.pivot.severity }}
                                        </span>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                    {{ condition.description }}
                                </p>
                            </div>
                        </div>
                        <div v-else class="text-center py-6">
                            <p class="text-gray-500 dark:text-gray-400">
                                No medical conditions added yet
                            </p>
                        </div>
                    </div>

                    <!-- Dietary Restrictions -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Your Dietary Restrictions
                        </h3>
                        <div v-if="hasRestrictions" class="space-y-3">
                            <div v-for="restriction in props.activeProfile.dietary_restrictions" :key="restriction.id" 
                                class="p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ restriction.name }}</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                    {{ restriction.description }}
                                </p>
                            </div>
                        </div>
                        <div v-else class="text-center py-6">
                            <p class="text-gray-500 dark:text-gray-400">
                                No dietary restrictions added yet
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Recent Profiles -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Your Dietary Profiles ({{ props.totalProfilesCount }})
                        </h3>
                        <Link 
                            :href="route('dietary-profile.create')" 
                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                        >
                            <span>New Profile</span>
                        </Link>
                    </div>
                    <div v-if="props.recentProfiles && props.recentProfiles.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="profile in props.recentProfiles" :key="profile.id" 
                            class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ profile.profile_name }}</span>
                                <span v-if="profile.is_active" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100">
                                    Active
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                {{ profile.description || 'No description available' }}
                            </p>
                            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                Last updated: {{ formatDate(profile.updated_at) }}
                            </div>
                            <div class="mt-3 flex space-x-3">
                                <Link 
                                    :href="route('dietary-profile.show', profile.id)" 
                                    class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
                                >
                                    View details →
                                </Link>
                                <Link 
                                    v-if="!profile.is_active"
                                    method="put"
                                    :href="route('dietary-profile.set-active', profile.id)"
                                    class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    Set as active
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div v-if="props.totalProfilesCount > props.recentProfiles.length" class="mt-4 text-center">
                        <Link 
                            :href="route('dietary-profile.index')" 
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                        >
                            <span>View All Profiles ({{ props.totalProfilesCount }})</span>
                        </Link>
                    </div>
                    <div v-else-if="!props.recentProfiles || props.recentProfiles.length === 0" class="text-center py-6">
                        <p class="text-gray-500 dark:text-gray-400 mb-4">
                            You don't have any dietary profiles yet.
                        </p>
                        <Link 
                            :href="route('dietary-profile.create')" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                        >
                            Create Your First Profile
                        </Link>
                    </div>
                </div>

                <!-- Recipe Recommendations Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Recommended Recipes
                    </h3>
                    
                    <div v-if="!hasActiveProfile" class="text-center py-6">
                        <p class="text-gray-500 dark:text-gray-400">
                            Create a dietary profile to get personalized recipe recommendations
                        </p>
                    </div>
                    
                    <div v-else-if="props.recommendedRecipes && props.recommendedRecipes.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Recipe cards would go here -->
                        <div v-for="(recipe, index) in 3" :key="index" class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="h-40 bg-gray-200 dark:bg-gray-700"></div>
                            <div class="p-4">
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">Recipe placeholder</h4>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Recipe recommendations coming soon based on your dietary profile.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-6">
                        <p class="text-gray-500 dark:text-gray-400">
                            Recipe recommendations will appear here once the recipe feature is available.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
