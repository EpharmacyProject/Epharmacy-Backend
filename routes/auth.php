<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\DrugConroller;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\Profile\PasswordController;
use App\Http\Controllers\Api\DrugLikeController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PatientController; 
use App\Http\Controllers\Api\PharmacistController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ResetPasswordController;
use App\Http\Controllers\Api\InventoryLogController;
use App\Http\Controllers\Api\PlaceController;






// Authentication Routes
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/refresh_token', [AuthController::class, 'refreshToken']);
Route::post('auth/verify_user_email', [AuthController::class, 'verifyUserEmail']);
Route::post('auth/resend_email_verification_link', [AuthController::class, 'resendEmailVerificationLink']);
Route::post('/forgot-password', [PasswordController::class, 'sendResetLink']);

//Google oauth Routes

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
Route::get('/auth/google', [GoogleAuthController::class ,'redirect'])->name('google-auth');

//Places Route

Route::get('/user-locations', [PlaceController::class, 'userLocations']);
Route::get('/app', function () {
    return view('app');
});


Route::middleware(['auth'])->group(function () {
    // Authenticated User Routes
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/profile', [AuthController::class, 'profile']);
    Route::post('auth/change_password', [PasswordController::class, 'changeUserPassword']);

    // Drug Like Routes
    Route::post('togglelike', [DrugLikeController::class, 'togglelike']);


    //Prescription Routes
    Route::post('/prescriptions/upload', [PrescriptionController::class, 'upload']);
   
    // Order Routes

    Route::get('/orders', [OrderController::class, 'userOrders']); // for regular users
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);

    //Image Routes
    Route::post('/image', [ImageController::class, 'store']);      
    Route::get('/image', [ImageController::class, 'show']);        
    Route::delete('/image', [ImageController::class, 'destroy']); 
    
  
    

    // Pharmacist Routes
    Route::get('/pharmacists', [PharmacistController::class, 'index']); 

    

    //Prescription Route 
    Route::post('/prescriptions', [PrescriptionController::class, 'upload']);
    

  

    //Message Routes
    Route::post('/messages/send', [MessageController::class, 'sendMessage']);
    // Get conversation with a specific user (2-way chat)
    Route::get('/messages/conversation/{userId}', [MessageController::class, 'getConversationWithUser']);
    // Mark a message as read
    Route::patch('/messages/{id}/read', [MessageController::class, 'markAsRead']);
    Route::delete('/messages/{id}', [MessageController::class, 'deleteMessage']);

    // Admin Routes (Restricted)
    Route::middleware(['admin'])->group(function () {
        Route::get('admin/pharmacists', [PharmacistController::class, 'index']); 
        Route::get('admin/patients', [PatientController::class, 'getAllPatients']); 
        Route::get('admin/patients/{id}', [PatientController::class, 'show']); 
        Route::put('admin/patients/{id}', [PatientController::class, 'update']); 
        Route::delete('admin/patients/{id}', [PatientController::class, 'destroy']); 

        Route::get('pharmacists/{id}/license-image', [AdminController::class, 'viewLicenseImage']);
        Route::put('pharmacists/{id}/approve', [AdminController::class, 'approvePharmacist']);
        Route::put('pharmacists/{id}/reject', [AdminController::class, 'rejectPharmacist']);

        Route::post('admin/pharmacists', [PharmacistController::class, 'store']); 
        Route::put('admin/pharmacists/{pharmacist}', [PharmacistController::class, 'update']); 
        Route::delete('admin/pharmacists/{pharmacist}', [PharmacistController::class, 'destroy']); 

        //order admin routes 
        
        
        Route::get('/admin/orders', [OrderController::class, 'adminOrders']);
    });

    // Pharmacist Routes
    Route::middleware(['pharmacist'])->group(function () {
        Route::post('pharmacist/drug/{id}', [PharmacistController::class, 'updateDrug']);
        Route::delete('pharmacist/drug/{id}', [PharmacistController::class, 'deleteDrug']);

        //Prescriptions Route
        Route::post('/prescriptions/dispense/{uid}', [PrescriptionController::class, 'dispense']);
        //Inventory logs
        Route::get('inventory/logs', [InventoryLogController::class, 'index']);
        //Stocks
        Route::get('/low-stock/alerts', [DrugConroller::class, 'lowStockAlerts']); 
        Route::patch('/{drug}/adjust-stock', [DrugConroller::class, 'adjustStock']);
          
        // Drug Routes
        Route::put('drug/{id}', [DrugConroller::class, 'update']); 
        Route::post('drugs', [DrugConroller::class, 'store']); 
        Route::delete('drug/{id}', [DrugConroller::class, 'delete']); 


    });

    
});

// Public Drug Routes
Route::get('drugs', [DrugConroller::class, 'index']); 
Route::get('drugs/{id}', [DrugConroller::class, 'show']); 




