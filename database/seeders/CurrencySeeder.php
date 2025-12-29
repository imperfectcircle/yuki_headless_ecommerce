<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'EUR',
                'symbol' => '€',
                'name' => 'Euro',
                'precision' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'USD',
                'symbol' => '$',
                'name' => 'US Dollar',
                'precision' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'GBP',
                'symbol' => '£',
                'name' => 'British Pound',
                'precision' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $currency['code']],
                $currency
            );
        }

        $this->command->info('Currencies seeded successfully.');
    }
}
