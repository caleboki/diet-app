<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MedicalCondition;

class MedicalConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Common medical conditions that have dietary impacts
        $conditions = [
            [
                'name' => 'Celiac Disease',
                'description' => 'An immune reaction to eating gluten, a protein found in wheat, barley, and rye.',
                'is_verified' => true
            ],
            [
                'name' => 'Lactose Intolerance',
                'description' => 'The inability to digest lactose, the sugar primarily found in milk and dairy products.',
                'is_verified' => true
            ],
            [
                'name' => 'Diabetes',
                'description' => 'A disease that affects how your body uses blood sugar (glucose).',
                'is_verified' => true
            ],
            [
                'name' => 'Hypertension',
                'description' => 'High blood pressure that can increase the risk of heart disease and stroke.',
                'is_verified' => true
            ],
            [
                'name' => 'IBS (Irritable Bowel Syndrome)',
                'description' => 'A common disorder that affects the large intestine and can cause cramping, abdominal pain, bloating, gas, and diarrhea or constipation.',
                'is_verified' => true
            ],
            [
                'name' => 'GERD (Gastroesophageal Reflux Disease)',
                'description' => 'A digestive disorder that affects the ring of muscle between your esophagus and your stomach.',
                'is_verified' => true
            ],
            [
                'name' => 'Crohn\'s Disease',
                'description' => 'A type of inflammatory bowel disease that can affect any part of the digestive tract from mouth to anus.',
                'is_verified' => true
            ],
            [
                'name' => 'Ulcerative Colitis',
                'description' => 'An inflammatory bowel disease that causes long-lasting inflammation and ulcers in your digestive tract.',
                'is_verified' => true
            ],
            [
                'name' => 'Kidney Disease',
                'description' => 'Conditions that damage your kidneys and decrease their ability to function properly.',
                'is_verified' => true
            ],
            [
                'name' => 'Food Allergies',
                'description' => 'An immune system reaction that occurs soon after eating a certain food.',
                'is_verified' => true
            ],
            [
                'name' => 'Fatty Liver Disease',
                'description' => 'A condition where too much fat builds up in the liver.',
                'is_verified' => true
            ],
            [
                'name' => 'Phenylketonuria (PKU)',
                'description' => 'A rare inherited disorder that causes phenylalanine to build up in the body.',
                'is_verified' => true
            ],
            [
                'name' => 'Gallbladder Disease',
                'description' => 'Conditions that affect the gallbladder, a small organ that aids in the digestion of fats.',
                'is_verified' => true
            ],
            [
                'name' => 'Gout',
                'description' => 'A common form of inflammatory arthritis that causes sudden, severe attacks of pain, swelling, redness, and tenderness in the joints.',
                'is_verified' => true
            ],
            [
                'name' => 'Pancreatitis',
                'description' => 'Inflammation in the pancreas, which can affect digestion and metabolism.',
                'is_verified' => true
            ]
        ];
        
        foreach ($conditions as $condition) {
            MedicalCondition::updateOrCreate(
                ['name' => $condition['name']],
                $condition
            );
        }
    }
}
