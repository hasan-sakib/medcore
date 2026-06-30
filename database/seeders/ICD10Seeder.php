<?php

namespace Database\Seeders;

use App\Models\Diagnosis;
use Illuminate\Database\Seeder;

class ICD10Seeder extends Seeder
{
    public function run(): void
    {
        $diagnoses = [
            // Infectious & Parasitic
            ['icd10_code' => 'A09',   'description' => 'Other gastroenteritis and colitis of infectious and unspecified origin', 'category' => 'Infectious diseases'],
            ['icd10_code' => 'A41.9', 'description' => 'Sepsis, unspecified organism', 'category' => 'Infectious diseases'],
            ['icd10_code' => 'B34.9', 'description' => 'Viral infection, unspecified', 'category' => 'Infectious diseases'],

            // Cardiovascular
            ['icd10_code' => 'I10',   'description' => 'Essential (primary) hypertension', 'category' => 'Circulatory system'],
            ['icd10_code' => 'I21.9', 'description' => 'Acute myocardial infarction, unspecified', 'category' => 'Circulatory system'],
            ['icd10_code' => 'I25.10', 'description' => 'Atherosclerotic heart disease of native coronary artery without angina pectoris', 'category' => 'Circulatory system'],
            ['icd10_code' => 'I50.9', 'description' => 'Heart failure, unspecified', 'category' => 'Circulatory system'],
            ['icd10_code' => 'I48.91', 'description' => 'Unspecified atrial fibrillation', 'category' => 'Circulatory system'],
            ['icd10_code' => 'I63.9', 'description' => 'Cerebral infarction, unspecified', 'category' => 'Circulatory system'],

            // Respiratory
            ['icd10_code' => 'J06.9', 'description' => 'Acute upper respiratory infection, unspecified', 'category' => 'Respiratory system'],
            ['icd10_code' => 'J18.9', 'description' => 'Pneumonia, unspecified organism', 'category' => 'Respiratory system'],
            ['icd10_code' => 'J44.1', 'description' => 'Chronic obstructive pulmonary disease with acute exacerbation', 'category' => 'Respiratory system'],
            ['icd10_code' => 'J45.901', 'description' => 'Unspecified asthma, uncomplicated', 'category' => 'Respiratory system'],
            ['icd10_code' => 'J96.0', 'description' => 'Acute respiratory failure', 'category' => 'Respiratory system'],

            // Endocrine & Metabolic
            ['icd10_code' => 'E11.9', 'description' => 'Type 2 diabetes mellitus without complications', 'category' => 'Endocrine & metabolic'],
            ['icd10_code' => 'E10.9', 'description' => 'Type 1 diabetes mellitus without complications', 'category' => 'Endocrine & metabolic'],
            ['icd10_code' => 'E11.65', 'description' => 'Type 2 diabetes mellitus with hyperglycemia', 'category' => 'Endocrine & metabolic'],
            ['icd10_code' => 'E03.9', 'description' => 'Hypothyroidism, unspecified', 'category' => 'Endocrine & metabolic'],
            ['icd10_code' => 'E66.01', 'description' => 'Morbid (severe) obesity due to excess calories', 'category' => 'Endocrine & metabolic'],
            ['icd10_code' => 'E78.5', 'description' => 'Hyperlipidemia, unspecified', 'category' => 'Endocrine & metabolic'],

            // Digestive system
            ['icd10_code' => 'K21.0', 'description' => 'Gastro-esophageal reflux disease with esophagitis', 'category' => 'Digestive system'],
            ['icd10_code' => 'K29.70', 'description' => 'Gastritis, unspecified, without bleeding', 'category' => 'Digestive system'],
            ['icd10_code' => 'K35.80', 'description' => 'Other and unspecified acute appendicitis without abscess', 'category' => 'Digestive system'],
            ['icd10_code' => 'K40.90', 'description' => 'Unilateral inguinal hernia, without obstruction or gangrene, not specified as recurrent', 'category' => 'Digestive system'],
            ['icd10_code' => 'K57.30', 'description' => 'Diverticulosis of large intestine without perforation or abscess without bleeding', 'category' => 'Digestive system'],
            ['icd10_code' => 'K92.1', 'description' => 'Melena', 'category' => 'Digestive system'],

            // Musculoskeletal
            ['icd10_code' => 'M10.9', 'description' => 'Gout, unspecified', 'category' => 'Musculoskeletal'],
            ['icd10_code' => 'M17.11', 'description' => 'Primary osteoarthritis, right knee', 'category' => 'Musculoskeletal'],
            ['icd10_code' => 'M54.5', 'description' => 'Low back pain', 'category' => 'Musculoskeletal'],
            ['icd10_code' => 'M79.3', 'description' => 'Panniculitis, unspecified', 'category' => 'Musculoskeletal'],

            // Neurological
            ['icd10_code' => 'G20',   'description' => 'Parkinson\'s disease', 'category' => 'Nervous system'],
            ['icd10_code' => 'G35',   'description' => 'Multiple sclerosis', 'category' => 'Nervous system'],
            ['icd10_code' => 'G40.909', 'description' => 'Epilepsy, unspecified, not intractable, without status epilepticus', 'category' => 'Nervous system'],
            ['icd10_code' => 'G43.909', 'description' => 'Migraine, unspecified, not intractable, without status migrainosus', 'category' => 'Nervous system'],

            // Genitourinary
            ['icd10_code' => 'N17.9', 'description' => 'Acute kidney failure, unspecified', 'category' => 'Genitourinary'],
            ['icd10_code' => 'N18.3', 'description' => 'Chronic kidney disease, stage 3 (moderate)', 'category' => 'Genitourinary'],
            ['icd10_code' => 'N39.0', 'description' => 'Urinary tract infection, site not specified', 'category' => 'Genitourinary'],

            // Mental health
            ['icd10_code' => 'F32.9', 'description' => 'Major depressive disorder, single episode, unspecified', 'category' => 'Mental health'],
            ['icd10_code' => 'F41.1', 'description' => 'Generalized anxiety disorder', 'category' => 'Mental health'],

            // Neoplasms
            ['icd10_code' => 'C34.10', 'description' => 'Malignant neoplasm of upper lobe, bronchus or lung, unspecified side', 'category' => 'Neoplasms'],
            ['icd10_code' => 'C50.911', 'description' => 'Malignant neoplasm of unspecified site of right female breast', 'category' => 'Neoplasms'],
            ['icd10_code' => 'C61',   'description' => 'Malignant neoplasm of prostate', 'category' => 'Neoplasms'],
            ['icd10_code' => 'C18.9', 'description' => 'Malignant neoplasm of colon, unspecified', 'category' => 'Neoplasms'],

            // Injury & External causes
            ['icd10_code' => 'S72.001A', 'description' => 'Fracture of unspecified part of neck of right femur, initial encounter for closed fracture', 'category' => 'Injury'],
            ['icd10_code' => 'T14.90', 'description' => 'Injury, unspecified, initial encounter', 'category' => 'Injury'],

            // Factors influencing health
            ['icd10_code' => 'Z87.891', 'description' => 'Personal history of other specified conditions', 'category' => 'Health status factors'],
            ['icd10_code' => 'Z79.4', 'description' => 'Long-term (current) use of insulin', 'category' => 'Health status factors'],
            ['icd10_code' => 'Z79.01', 'description' => 'Long-term (current) use of anticoagulants', 'category' => 'Health status factors'],
        ];

        foreach ($diagnoses as $diagnosis) {
            Diagnosis::updateOrCreate(
                ['icd10_code' => $diagnosis['icd10_code']],
                $diagnosis + ['is_active' => true]
            );
        }
    }
}
