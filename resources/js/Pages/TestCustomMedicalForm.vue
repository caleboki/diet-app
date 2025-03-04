<template>
  <AppLayout title="Test Custom Medical Condition Form">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Test Custom Medical Condition Form
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
          <h3 class="text-lg font-medium mb-6">Test Page</h3>
          
          <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
            <CustomMedicalConditionForm :on-condition-created="handleCreated" />
          </div>
          
          <div v-if="createdCondition" class="mt-8 p-4 bg-green-50 border border-green-200 rounded-lg">
            <h3 class="font-medium">Created Condition:</h3>
            <p>{{ createdCondition.name }}</p>
            <p>{{ createdCondition.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomMedicalConditionForm from '@/Components/CustomMedicalConditionForm.vue';

const createdCondition = ref(null);

const handleCreated = (condition) => {
  console.log('Condition created:', condition);
  createdCondition.value = condition;
};
</script>
