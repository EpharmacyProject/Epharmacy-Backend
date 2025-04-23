<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PharmacistResource;
use App\Models\Pharmacist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    
   

    
    public function viewLicenseImage($id)
    {
        $pharmacist = Pharmacist::find($id);

        if (!$pharmacist) {
            return response()->json([
                'message' => 'Pharmacist not found'
            ], 404);
        }

        if (!$pharmacist->license_image) {
            return response()->json([
                'message' => 'No license image found for this pharmacist'
            ], 404);
        }

        
        return response()->json([
            'message' => 'License image fetched successfully',
            'data' => asset('app/public/' .  $pharmacist->license_image)
        ]);
    }


    public function approvePharmacist(Request $request, $id)
    {
        $pharmacist = Pharmacist::find($id);

        if (!$pharmacist) {
            return response()->json([
                'message' => 'Pharmacist not found'
            ], 404);
        }

        
        if (!$pharmacist->license_image) {
            return response()->json([
                'message' => 'No license image available for this pharmacist'
            ], 422);
        }

        
        $pharmacist->status = 'approved';
        $pharmacist->save();

       
        $user = User::find($pharmacist->user_id);
        if ($user) {
            $user->status = 'approved';
            $user->save();
        }

        return response()->json([
            'message' => 'Pharmacist approved and registration updated successfully',
            'data' => new PharmacistResource($pharmacist)
        ], 200);
    }

    
    public function rejectPharmacist($id)
    {
        $pharmacist = Pharmacist::find($id);

        if (!$pharmacist) {
            return response()->json([
                'message' => 'Pharmacist not found'
            ], 404);
        }

        
        $pharmacist->status = 'rejected';
        $pharmacist->save();

        
        $user = User::find($pharmacist->user_id);
        if ($user) {
            $user->status = 'rejected';
            $user->save();
        }

        return response()->json([
            'message' => 'Pharmacist registration rejected',
            'data' => new PharmacistResource($pharmacist)
        ], 200);
    }

   
}
