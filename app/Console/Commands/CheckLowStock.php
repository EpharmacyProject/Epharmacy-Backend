<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Drug;
use App\Notifications\LowStockAlertNotification;
use Illuminate\Support\Facades\Log;

class CheckLowStock extends Command
{
    protected $signature = 'drugs:check-low-stock';
    protected $description = 'Check for drugs with low stock and send notifications';

    public function handle()
    {
        try {
            $lowStockDrugs = Drug::where('stock', '<', 10)->get();

            if ($lowStockDrugs->isEmpty()) {
                $this->info('No low stock drugs found.');
                return;
            }

            foreach ($lowStockDrugs as $drug) {
                $pharmacist = $drug->creator;
                if ($pharmacist) {
                    try {
                        $pharmacist->notify(new LowStockAlertNotification($drug));
                        Log::info('Low stock notification sent to pharmacist', [
                            'pharmacist_id' => $pharmacist->id,
                            'drug_id' => $drug->id,
                            'stock' => $drug->stock
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send low stock notification', [
                            'pharmacist_id' => $pharmacist->id,
                            'drug_id' => $drug->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            $this->info('Low stock notifications sent successfully.');
        } catch (\Exception $e) {
            Log::error('Error in CheckLowStock command: ' . $e->getMessage());
            $this->error('An error occurred while checking low stock: ' . $e->getMessage());
        }
    }
} 