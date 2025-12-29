<?php

namespace Database\Seeders;

use App\Domains\Payments\Models\PaymentProviderConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'code' => 'stripe',
                'enabled' => false,
                'position' => 1,
            ],
            [
                'code' => 'paypal',
                'enabled' => false,
                'position' => 2,
            ],
        ];

        foreach ($providers as $provider) {
            PaymentProviderConfig::updateOrCreate(
                ['code' => $provider['code']],
                $provider
            );
        }

        $this->command->info('Payment providers seeded successfully.');
    }
}
