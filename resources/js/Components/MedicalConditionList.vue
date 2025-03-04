<template>
  <div class="medical-condition-list">
    <div v-if="loading" class="flex justify-center py-4">
      <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-indigo-500"></div>
    </div>
    
    <div v-else>
      <div class="flex mb-4">
        <TextInput
          v-model="searchQuery"
          type="text"
          class="w-full"
          placeholder="Search medical conditions..."
        />
      </div>
      
      <div v-if="filteredConditions.length === 0" class="text-center py-4 text-gray-500">
        No conditions found matching your search.
      </div>
      
      <div v-else class="space-y-3">
        <div
          v-for="condition in filteredConditions"
          :key="condition.id"
          class="p-3 border rounded-md"
          :class="{
            'border-indigo-200 bg-indigo-50 dark:border-indigo-900 dark:bg-indigo-950/30': condition.is_custom,
            'border-gray-200 dark:border-gray-700': !condition.is_custom
          }"
        >
          <div class="flex items-start justify-between">
            <div>
              <h4 class="font-medium">
                {{ condition.name }}
                <span v-if="condition.is_custom" class="ml-2 px-2 py-0.5 text-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 rounded-full">
                  Custom
                </span>
              </h4>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ condition.description }}
              </p>
              <div v-if="condition.is_custom" class="mt-2 text-xs text-gray-500">
                Added by you
              </div>
            </div>
            
            <div class="flex">
              <button
                v-if="!condition.is_verified && condition.is_custom"
                @click="emit('delete', condition)"
                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                title="Delete custom condition"
              >
                <span class="sr-only">Delete</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
  conditions: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['select', 'delete']);

const searchQuery = ref('');

const filteredConditions = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.conditions;
  }
  
  const query = searchQuery.value.toLowerCase();
  return props.conditions.filter(condition => 
    condition.name.toLowerCase().includes(query) || 
    condition.description.toLowerCase().includes(query)
  );
});
</script>
