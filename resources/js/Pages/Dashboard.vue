<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const activeProfile = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await fetch('/dietary-profile');
        const data = await response.json();
        if (data.profiles && data.profiles.length > 0) {
            activeProfile.value = data.profiles.find(p => p.is_active) || data.profiles[0];
        }
    } catch (error) {
        console.error('Error fetching profile data:', error);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Dietary Profile Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                    <div v-if="loading" class="flex justify-center items-center py-8">
                        <svg class="animate-spin h-8 w-8 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    
                    <div v-else-if="activeProfile" class="flex flex-col md:flex-row items-start md:items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Active Dietary Profile
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                You're currently using the "{{ activeProfile.profile_name }}" profile with {{ activeProfile.dietary_restrictions?.length || 0 }} dietary restrictions.
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span 
                                    v-for="restriction in activeProfile.dietary_restrictions" 
                                    :key="restriction.id"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100"
                                >
                                    {{ restriction.name }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-4 md:mt-0 flex space-x-3">
                            <Link 
                                :href="route('dietary-profile.show', activeProfile.id)" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                            >
                                View Profile
                            </Link>
                            <Link 
                                :href="route('dietary-profile.edit', activeProfile.id)" 
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
                                Create a dietary profile to get personalized recipe recommendations based on your health conditions and preferences.
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

                <!-- Recipe Recommendations Section placeholder -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Recommended Recipes
                    </h3>
                    
                    <div v-if="!activeProfile" class="text-center py-6">
                        <p class="text-gray-500 dark:text-gray-400">
                            Create a dietary profile to get personalized recipe recommendations
                        </p>
                    </div>
                    
                    <div v-else class="text-center py-6">
                        <p class="text-gray-500 dark:text-gray-400">
                            Recipe recommendations will appear here once your profile is set up
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
