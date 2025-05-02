<?php
namespace App\Http\Controllers;

use App\Customs\Services\CloudinaryService;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImageController extends Controller
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user = Auth::user();

        // Delete existing image if any
        $existingImage = Image::where('user_id', $user->id)->first();
        if ($existingImage) {
            if ($existingImage->public_id) {
                $this->cloudinaryService->deleteImage($existingImage->public_id);
            }
            $existingImage->delete();
        }

        // Upload new image to Cloudinary
        $result = $this->cloudinaryService->uploadImage($request->file('image'), 'profile_pictures');
        
        $image = Image::create([
            'user_id' => $user->id,
            'image_path' => $result['secure_url'],
            'public_id' => $result['public_id']
        ]);

        return response()->json([
            'message' => 'Profile picture updated successfully',
            'image' => $image
        ], 201);
    }

    public function show()
    {
        $userId = Auth::id();
        $image = Image::where('user_id', $userId)->first();

        return $image
            ? response()->json($image, 200)
            : response()->json(['message' => 'No profile picture found'], 404);
    }

    public function update(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $userId = Auth::id();
        $existingImage = Image::where('user_id', $userId)->first();

        if ($existingImage) {
            if ($existingImage->public_id) {
                $this->cloudinaryService->deleteImage($existingImage->public_id);
            }
            $existingImage->delete();
        }

        $result = $this->cloudinaryService->uploadImage($request->file('image'), 'profile_pictures');
        
        $image = Image::create([
            'user_id' => $userId,
            'image_path' => $result['secure_url'],
            'public_id' => $result['public_id']
        ]);

        return response()->json([
            'message' => 'Profile picture updated successfully',
            'image' => $image
        ], 200);
    }

    public function destroy()
    {
        $userId = Auth::id();
        $image = Image::where('user_id', $userId)->first();
        
        if (!$image) {
            return response()->json(['message' => 'No profile picture found'], 404);
        }

        if ($image->public_id) {
            $this->cloudinaryService->deleteImage($image->public_id);
        }

        $image->delete();

        return response()->json(['message' => 'Profile picture deleted successfully'], 200);
    }
}
