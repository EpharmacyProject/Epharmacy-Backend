<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use Illuminate\Http\Request;

class DrugController extends Controller
{
    public function getMyDrugs()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'User not authenticated'
                ], 401);
            }

            $drugs = Drug::where('created_by', $user->id)
                        ->get();

            return response()->json([
                'status' => 'success',
                'data' => $drugs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error fetching drugs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 