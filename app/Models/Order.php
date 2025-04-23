<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'items',
        'total_amount',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
