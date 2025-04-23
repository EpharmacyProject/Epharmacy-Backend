<?php
namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\Request;
use App\Customs\Services\PasswordService;

class PasswordController extends Controller
{
    public function __construct(public PasswordService $service) {}

   
    public function changeUserPassword(ChangePasswordRequest $request)
    {
        return $this->service->changePassword($request->validated());
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']); 
        return $this->service->sendResetLink($request->email);
    }
    
    

}
