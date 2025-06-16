<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FilterSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $filters = [
            [
                'name' => 'fake_gpu_filter',
                'description' => 'Exclude resources with "No discrete GPU detected"',
                'enabled' => true,
                'config' => json_encode([
                    'excluded_gpu_names' => ['No discrete GPU detected'],
                    'check_field' => 'gpu_specs.model_name'
                ]),
                'order' => 1
            ],
            [
                'name' => 'storage_filter',
                'description' => 'Exclude resources with invalid storage',
                'enabled' => true,
                'config' => json_encode([
                    'min_storage_gb' => 1,
                    'check_field' => 'storage.total_gb'
                ]),
                'order' => 2
            ],
            [
                'name' => 'verification_filter',
                'description' => 'Only show verified resources',
                'enabled' => true,
                'config' => json_encode([
                    'required_status' => 'verified',
                    'check_field' => 'validation_status'
                ]),
                'order' => 3
            ],
            [
                'name' => 'activity_filter',
                'description' => 'Only show active resources',
                'enabled' => true,
                'config' => json_encode([
                    'required_value' => true,
                    'check_field' => 'is_active'
                ]),
                'order' => 4
            ],
            [
                'name' => 'monitoring_filter',
                'description' => 'Only show resources with healthy monitoring',
                'enabled' => true,
                'config' => json_encode([
                    'required_auth_status' => 'ok',
                    'required_conn_status' => 'ok',
                    'required_docker_running' => true,
                    'required_docker_user_group' => true
                ]),
                'order' => 5
            ]
        ];

        foreach ($filters as $filter) {
            DB::table('filter_settings')->insert(array_merge($filter, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}