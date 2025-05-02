<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PharmacistStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PharmacistController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin');
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:approved,rejected',
                'reason' => 'required_if:status,rejected|string|nullable'
            ]);

            $pharmacist = User::findOrFail($id);

            if ($pharmacist->role !== 'pharmacist') {
                return response()->json(['message' => 'User is not a pharmacist'], 400);
            }

            DB::beginTransaction();

            try {
                // Update only the status
                $pharmacist->update([
                    'status' => $request->status,
                    'status_reason' => $request->reason,
                    'status_updated_at' => now()
                ]);

                // Send notification
                $pharmacist->notify(new PharmacistStatusUpdated(
                    $request->status,
                    $request->reason
                ));

                DB::commit();

                return response()->json([
                    'message' => 'Pharmacist status updated successfully',
                    'data' => $pharmacist
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error updating pharmacist status: ' . $e->getMessage());
                return response()->json(['message' => 'Error updating pharmacist status'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in updateStatus: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing request'], 500);
        }
    }
} 