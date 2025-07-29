<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'John Doe',
                'phone_number' => '+254700000001',
                'title' => 'Mr.',
                'physical_location' => 'Nairobi, Kenya',
                'status' => 'Active',
            ],
            [
                'name' => 'Jane Smith',
                'phone_number' => '+254700000002',
                'title' => 'Ms.',
                'physical_location' => 'Mombasa, Kenya',
                'status' => 'Active',
            ],
            [
                'name' => 'Michael Johnson',
                'phone_number' => '+254700000003',
                'title' => 'Dr.',
                'physical_location' => 'Kisumu, Kenya',
                'status' => 'Active',
            ],
            [
                'name' => 'Sarah Wilson',
                'phone_number' => '+254700000004',
                'title' => 'Mrs.',
                'physical_location' => 'Nakuru, Kenya',
                'status' => 'Active',
            ],
            [
                'name' => 'David Brown',
                'phone_number' => '+254700000005',
                'title' => 'Mr.',
                'physical_location' => 'Eldoret, Kenya',
                'status' => 'Active',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::firstOrCreate(
                ['phone_number' => $customer['phone_number']],
                $customer
            );
        }
    }
} 