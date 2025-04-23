<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'prescription_file' => 'required|file|mimes:pdf,jpg,jpeg,png',
            'refill_allowed' => 'nullable|integer|min:1',
        ]);

        $user = $request->user(); 
        $refillAllowed = $request->refill_allowed ?? 1;

        $file = $request->file('prescription_file');
        $fileContents = file_get_contents($file->getRealPath());
        $uid = hash('sha256', $fileContents);

      
        if (Prescription::where('prescription_uid', $uid)->exists()) {
            return response()->json([
                'error' => 'This prescription has already been uploaded.',
            ], 409);
        }

        $filePath = $file->store('prescriptions', 'public');

        $prescription = Prescription::create([
            'prescription_uid' => $uid,
            'user_id' => $user->id,
            'attachment_path' => $filePath,
            'refill_allowed' => $refillAllowed,
            'refill_used' => 0,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Prescription uploaded successfully.',
            'prescription_uid' => $prescription->prescription_uid,
            'refill_allowed' => $prescription->refill_allowed,
            'refill_used' => $prescription->refill_used,
            'status' => $prescription->status
        ], 201);
    }

    public function dispense(Request $request, $uid)
    {
        $user = $request->user();

       
        if ($user->is_role !== 2) {
            return response()->json(['error' => 'Only pharmacists can perform this action.'], 403);
        }

        
        $prescription = Prescription::where('prescription_uid', $uid)->first();
        if (!$prescription) {
            return response()->json(['error' => 'Prescription not found.'], 404);
        }

    
        if ($prescription->status === 'rejected') {
            return response()->json([
                'error' => 'Prescription is rejected.',
            ], 400);
        }


        if ($request->filled('refills_remaining')) {
            $prescription->refill_allowed = (int) $request->input('refills_remaining');
        }

   
        if ($prescription->refill_used >= $prescription->refill_allowed) {
            return response()->json(['warning' => 'Prescription already fulfilled.'], 403);
        }

    
        $prescription->increment('refill_used');

    
        if ($prescription->refill_used === $prescription->refill_allowed) {
            $prescription->status = 'fulfilled';
        } else {
            $prescription->status = 'partially_filled';
        }

        $prescription->save();


        return response()->json([
            'message' => 'Prescription dispensed.',
            'refills_remaining' => $prescription->refill_allowed - $prescription->refill_used,
            'status' => $prescription->status,
        ]);
    }
}
