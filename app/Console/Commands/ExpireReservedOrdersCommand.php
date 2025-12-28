<?php

namespace App\Console\Commands;

use App\Domains\Order\Actions\ExpireReservedOrders;
use Illuminate\Console\Command;

class ExpireReservedOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:expire-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release inventory for expired order reservations';

    /**
     * Execute the console command.
     */
    public function handle(ExpireReservedOrders $action): int
    {
        $this->info('Checking for expired order reservations...');

        try {
            $action->execute();
            $this->info('✓ Order reservations processed successfully');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ Failed to process order reservations: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
