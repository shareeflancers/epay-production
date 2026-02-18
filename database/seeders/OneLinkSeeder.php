<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Consumer;
use App\Models\ProfileDetail;
use App\Models\ActiveChallan;

class OneLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $consumer = Consumer::create([
            'consumer_type' => 'student',
            'identification_number' => '3320274765343',
            'consumer_number' => '11121_332244',
            'institution_id' => 111,
            'region_id' => 21,
            'is_active' => true
        ]);

        ProfileDetail::create([
            'profile_type' => 'student',
            'consumer_id' => $consumer->id,
            'name' => 'Samawat Arsalan Hyder',
            'is_active' => true
        ]);

        ActiveChallan::create([
            'consumer_id' => $consumer->id,
            'challan_no' => 'CH001',
            'status' => 'U',
            'tran_auth_id' => 'TX1234',
            'due_date' => now()->addDays(10),
            'amount_base' => 1000.00,
            'amount_arrears' => 0.00,
            'amount_within_dueDate' => 1000.00,
            'amount_after_dueDate' => 1100.00,
            'is_active' => true
        ]);

        $this->command->info('OneLink test data seeded!');
    }
}
