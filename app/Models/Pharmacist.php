<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Laravel\Scout\Attributes\SearchUsingPrefix;
use Illuminate\Support\Facades\Hash;

class Pharmacist extends Model
{
    use HasFactory, Searchable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'lat', 
        'lng', 
        'status', 
        'license_image', 
        'is_role',
    ];

   
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    #[SearchUsingPrefix(['status'])]
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
        ];
    }

    
    public function getPrescriptionImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    
    public function getLicenseImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }
}
