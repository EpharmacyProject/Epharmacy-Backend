<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Customs\Services\CloudinaryService;
use App\Services\AdminEmailService;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $cloudinaryService;
    protected $adminEmailService;
    protected $emailVerificationService;

    public function __construct(
        CloudinaryService $cloudinaryService,
        AdminEmailService $adminEmailService,
        EmailVerificationService $emailVerificationService
    ) {
        $this->cloudinaryService = $cloudinaryService;
        $this->adminEmailService = $adminEmailService;
        $this->emailVerificationService = $emailVerificationService;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'is_role' => 'required|integer|in:1,2',
            'license_image' => 'required_if:is_role,2|image|mimes:jpeg,png,jpg|max:2048',
            'tin_image' => 'required_if:is_role,2|image|mimes:jpeg,png,jpg|max:2048',
            'tin_number' => 'required_if:is_role,2|string|max:255',
            'account_number' => 'required_if:is_role,2|string|max:255',
            'bank_name' => 'required_if:is_role,2|string|max:255',
            'phone' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_role' => $request->is_role,
                'status' => $request->is_role == 2 ? 'pending' : 'approved',
                'tin_number' => $request->tin_number,
                'account_number' => $request->account_number,
                'bank_name' => $request->bank_name,
                'phone' => $request->phone,
            ]);

            if ($request->is_role == 2) {
                // Handle license and tin image uploads using CloudinaryService
                if ($request->hasFile('license_image')) {
                    $result = $this->cloudinaryService->uploadImage($request->file('license_image'), 'licenses');
                    $user->license_image = $result['secure_url'];
                    $user->license_public_id = $result['public_id'];
                }

                if ($request->hasFile('tin_image')) {
                    $result = $this->cloudinaryService->uploadImage($request->file('tin_image'), 'tin_documents');
                    $user->tin_image = $result['secure_url'];
                    $user->tin_public_id = $result['public_id'];
                }

                $user->save();

                // Send notification to admins using the AdminEmailService
                $this->adminEmailService->sendNewPharmacistNotification($user);
            }

            // Send verification email
            $this->emailVerificationService->sendVerificationEmail($user);

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'errors' => $validator->errors()], 422);
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['status' => 'failed', 'message' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Logged in successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_role' => $user->is_role,
                'status' => $user->status,
                'address' => $user->address,
                'pharmacy_name' => $user->pharmacy_name,
                'phone_number' => $user->phone_number,
                'lat' => $user->lat,
                'lng' => $user->lng,
                'license_image' => $user->license_image,
                'tin_image' => $user->tin_image,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ],
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'pharmacy_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:255',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $user->update($validator->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_role' => $user->is_role,
                    'status' => $user->status,
                    'address' => $user->address,
                    'pharmacy_name' => $user->pharmacy_name,
                    'phone_number' => $user->phone_number,
                    'lat' => $user->lat,
                    'lng' => $user->lng,
                    'license_image' => $user->license_image,
                    'tin_image' => $user->tin_image,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllPharmacists(Request $request)
    {
        // Check if user is admin
        if (Auth::user()->is_role !== 0) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized. Only admins can access this endpoint.'
            ], 403);
        }

        try {
            $query = User::where('is_role', 2); // Get only pharmacists

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search by name, email, or pharmacy name
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('email', 'like', "%{$searchTerm}%")
                      ->orWhere('pharmacy_name', 'like', "%{$searchTerm}%");
                });
            }

            // Sort by created_at by default, but allow custom sorting
            $sortBy = $request->sort_by ?? 'created_at';
            $sortOrder = $request->sort_order ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = $request->per_page ?? 10;
            $pharmacists = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Pharmacists retrieved successfully',
                'data' => $pharmacists->map(function($pharmacist) {
                    return [
                        'id' => $pharmacist->id,
                        'name' => $pharmacist->name,
                        'email' => $pharmacist->email,
                        'status' => $pharmacist->status,
                        'pharmacy_name' => $pharmacist->pharmacy_name,
                        'address' => $pharmacist->address,
                        'phone_number' => $pharmacist->phone_number,
                        'license_image' => $pharmacist->license_image,
                        'tin_image' => $pharmacist->tin_image,
                        'tin_number' => $pharmacist->tin_number,
                        'account_number' => $pharmacist->account_number,
                        'bank_name' => $pharmacist->bank_name,
                        'lat' => $pharmacist->lat,
                        'lng' => $pharmacist->lng,
                        'created_at' => $pharmacist->created_at,
                        'updated_at' => $pharmacist->updated_at
                    ];
                }),
                'meta' => [
                    'current_page' => $pharmacists->currentPage(),
                    'from' => $pharmacists->firstItem(),
                    'last_page' => $pharmacists->lastPage(),
                    'per_page' => $pharmacists->perPage(),
                    'to' => $pharmacists->lastItem(),
                    'total' => $pharmacists->total(),
                ],
                'links' => [
                    'first' => $pharmacists->url(1),
                    'last' => $pharmacists->url($pharmacists->lastPage()),
                    'prev' => $pharmacists->previousPageUrl(),
                    'next' => $pharmacists->nextPageUrl(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to retrieve pharmacists',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 