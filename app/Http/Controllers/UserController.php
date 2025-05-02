<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PharmacistResource;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Validate user role (0: Admin, 1: Patient, 2: Pharmacist)
            if (!in_array($user->is_role, [0, 1, 2])) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invalid user role'
                ], 400);
            }

            // Different validation rules based on user role
            $rules = [
                'name' => 'sometimes|string|max:255',
                'address' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|max:20',
                'lat' => 'sometimes|numeric|between:-90,90',
                'lng' => 'sometimes|numeric|between:-180,180',
            ];

            // Add pharmacist-specific rules
            if ($user->is_role === 2) { // Pharmacist
                $rules['pharmacy_name'] = 'sometimes|string|max:255';
                $rules['tin_number'] = 'sometimes|string|max:50';
                $rules['bank_name'] = 'sometimes|string|max:255';
                $rules['account_number'] = 'sometimes|string|max:50';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Only update the validated fields
            $validatedData = $validator->validated();
            
            // Remove any attempt to update email, license_image, or tin_image
            unset($validatedData['email']);
            unset($validatedData['license_image']);
            unset($validatedData['tin_image']);
            unset($validatedData['is_role']); // Prevent role changes
            unset($validatedData['status']); // Prevent status changes
            
            // Log the data being updated
            Log::info('Updating user profile', [
                'user_id' => $user->id,
                'data' => $validatedData
            ]);
            
            // Update the user
            $updated = $user->update($validatedData);

            if (!$updated) {
                Log::error('Failed to update user profile', [
                    'user_id' => $user->id,
                    'data' => $validatedData
                ]);
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Failed to update profile'
                ], 500);
            }

            // Refresh the user model
            $user = $user->fresh();

            // Log the updated user data
            Log::info('User profile updated successfully', [
                'user_id' => $user->id,
                'updated_data' => $user->toArray()
            ]);

            // Return appropriate resource based on user role
            if ($user->is_role === 2) { // Pharmacist
                return response()->json([
                    'status' => 'success',
                    'message' => 'Profile updated successfully',
                    'user' => new PharmacistResource($user)
                ]);
            } else { // Admin or Patient
                return response()->json([
                    'status' => 'success',
                    'message' => 'Profile updated successfully',
                    'user' => new PatientResource($user)
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'status' => 'failed',
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 