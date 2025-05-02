<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistraationRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ResendEmailVerificationLinkRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Carbon;
use App\Customs\Services\EmailVerificationService;
use App\Services\AdminEmailService;
use App\Customs\Services\CloudinaryService;
use App\Http\Resources\PharmacistResource;
use App\Http\Resources\PatientResource;

class AuthController extends Controller
{
    public function __construct(
        private EmailVerificationService $service,
        private AdminEmailService $adminEmailService,
        private CloudinaryService $cloudinaryService
    ) {}

    public function register(RegistraationRequest $request)
    {
        $data = $request->validated();
    
        if ($request->hasFile('license_image')) {
            $result = $this->cloudinaryService->uploadImage($request->file('license_image'), 'licenses');
            $data['license_image'] = $result['secure_url'];
        }
    
        if ($request->hasFile('tin_image')) {
            $result = $this->cloudinaryService->uploadImage($request->file('tin_image'), 'tin_documents');
            $data['tin_image'] = $result['secure_url'];
        }
    
        $data['is_role'] = $request->input('is_role', 1);
        
        if ($request->filled('place_name') && $request->filled('lat') && $request->filled('lng')) {
            $data['place_name'] = $request->input('place_name');
            $data['address'] = $request->input('address');
            $data['lat'] = $request->input('lat');
            $data['lng'] = $request->input('lng');
        }
    
        $user = User::create($data);
        
        // Send verification email
        $this->service->sendVerificationLink($user);
        
        // If user is a pharmacist, send notification to admin
        if ($user->is_role == 2) {
            $this->adminEmailService->sendNewPharmacistNotification($user);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'user' => $user
        ], 201);
    }
    

 
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['status' => 'failed', 'message' => 'Invalid credentials'], 401);
        }
    
        if ($user->is_role == 2) {
            if ($user->status == 'pending') {
                return response()->json(['status' => 'failed', 'message' => 'Your license is not verified yet. Please wait for approval.'], 403);
            }
    
            if ($user->status == 'rejected') {
                return response()->json(['status' => 'failed', 'message' => 'Your license has been declined. You are not allowed to log in.'], 403);
            }
        }
    
        if (!$user->email_verified_at) {
            return response()->json(['status' => 'failed', 'message' => 'Please verify your email before logging in.'], 403);
        }
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        $refreshToken = Hash::make(now());
        DB::table('refresh_tokens')->updateOrInsert(
            ['user_id' => $user->id],
            ['token' => $refreshToken, 'expires_at' => now()->addDay()]
        );
    
        $expiresIn = Carbon::now()->addMinutes(60)->timestamp;
    
        $roleRedirects = [
            '0' => 'admin/dashboard', 
            '1' => 'patient/dashboard', 
            '2' => 'pharmacist/dashboard' 
        ];
    
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_role' => $user->is_role,
                    'status' => $user->status,
                    'email_verified_at' => $user->email_verified_at,
                    'pharmacy_name' => $user->pharmacy_name,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'lat' => $user->lat,
                    'lng' => $user->lng,
                    'license_image' => $user->license_image,
                    'tin_image' => $user->tin_image,
                    'tin_number' => $user->tin_number,
                    'account_number' => $user->account_number,
                    'bank_name' => $user->bank_name,
                    'status_reason' => $user->status_reason,
                    'status_updated_at' => $user->status_updated_at
                ],
                'token' => $token,
                'refresh_token' => $refreshToken,
                'expires_in' => $expiresIn,
                'redirect_to' => $roleRedirects[$user->is_role] ?? 'dashboard'
            ]
        ]);
    }
    

    public function refreshToken(Request $request)
    {
        $user = Auth::user();
        $refreshToken = $request->input('refresh_token');

        $storedToken = DB::table('refresh_tokens')->where('user_id', $user->id)->first();

        if (!$storedToken || !Hash::check($refreshToken, $storedToken->token)) {
            return response()->json(['status' => 'failed', 'message' => 'Invalid refresh token.'], 401);
        }

        if (Carbon::parse($storedToken->expires_at)->isPast()) {
            return response()->json(['status' => 'failed', 'message' => 'Refresh token expired. Please log in again.'], 401);
        }

        $newAccessToken = Auth::login($user);
        $newRefreshToken = Hash::make(now());

        DB::table('refresh_tokens')->where('user_id', $user->id)->update([
            'token' => $newRefreshToken, 'expires_at' => now()->addDay()
        ]);

        return response()->json([
            'status' => 'success',
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'bearer'
        ]);
    }

    public function verifyUserEmail(VerifyEmailRequest $request)
    {
        return $this->service->verifyEmail($request->email, $request->token);
    }

    public function resendEmailVerificationLink(ResendEmailVerificationLinkRequest $request)
    {
        return $this->service->resendLink($request->email);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink($request->only('email'));
        return response()->json(['status' => $status === Password::RESET_LINK_SENT ? 'success' : 'failed', 'message' => __($status)]);
    }



public function profile()
{
    $user = Auth::user();

    if (!$user) {
        return response()->json(['status' => false, 'message' => 'User is not authenticated'], 401);
    }

   
    if (!$user->email_verified_at) {
        return response()->json(['status' => false, 'message' => 'Please verify your email before accessing profile'], 403);
    }

   
    if ($user->is_role == 2) {  
        return new PharmacistResource($user);
    }

    if ($user->is_role == 1) {  
        return new PatientResource($user);
    }

    
    return response()->json(['status' => false, 'message' => 'Unauthorized role'], 403);
}

    

    public function logout()
    {
        Auth::logout();
        return response()->json(['status' => 'success', 'message' => 'User has been logged out successfully']);
    }

   
   
}
