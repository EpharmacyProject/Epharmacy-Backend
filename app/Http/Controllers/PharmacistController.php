<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pharmacist;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PharmacistController extends Controller
{
    public function index()
    {
        $pharmacists = Pharmacist::with('user')->get();
        return response()->json($pharmacists);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'license_number' => 'required|string|unique:pharmacists',
            'license_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'pharmacy_name' => 'required|string',
            'pharmacy_address' => 'required|string',
            'pharmacy_phone' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $pharmacist = new Pharmacist();
            $pharmacist->user_id = $validated['user_id'];
            $pharmacist->license_number = $validated['license_number'];
            $pharmacist->pharmacy_name = $validated['pharmacy_name'];
            $pharmacist->pharmacy_address = $validated['pharmacy_address'];
            $pharmacist->pharmacy_phone = $validated['pharmacy_phone'];
            $pharmacist->status = 'pending';

            if ($request->hasFile('license_image')) {
                $path = $request->file('license_image')->store('pharmacist_licenses', 'public');
                $pharmacist->license_image = $path;
            }

            $pharmacist->save();
            DB::commit();

            return response()->json([
                'message' => 'Pharmacist application submitted successfully',
                'data' => $pharmacist
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to submit pharmacist application',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Pharmacist $pharmacist)
    {
        $validated = $request->validate([
            'pharmacy_name' => 'sometimes|required|string',
            'pharmacy_address' => 'sometimes|required|string',
            'pharmacy_phone' => 'sometimes|required|string',
            'license_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('license_image')) {
                // Delete old license image if exists
                if ($pharmacist->license_image) {
                    Storage::disk('public')->delete($pharmacist->license_image);
                }
                $path = $request->file('license_image')->store('pharmacist_licenses', 'public');
                $pharmacist->license_image = $path;
            }

            $pharmacist->update($validated);
            DB::commit();

            return response()->json([
                'message' => 'Pharmacist information updated successfully',
                'data' => $pharmacist
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update pharmacist information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Pharmacist $pharmacist)
    {
        DB::beginTransaction();
        try {
            if ($pharmacist->license_image) {
                Storage::disk('public')->delete($pharmacist->license_image);
            }
            $pharmacist->delete();
            DB::commit();

            return response()->json([
                'message' => 'Pharmacist deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete pharmacist',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 