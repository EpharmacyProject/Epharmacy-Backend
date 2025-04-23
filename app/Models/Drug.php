<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Testing\Fluent\Concerns\Has;
use Laravel\Scout\Searchable;

class Drug extends Model
{
    use HasFactory, Searchable;
    protected $table = 'drugs';
  
    protected $casts = [
        'expires_at' => 'datetime',  
    ];
    protected $fillable = [
        'name',
        'description',
        'expires_at',
        'brand',
        'category',
        'price',
        'stock',
        'dosage',
    ];
    public function toSearchableArray(){
        return [
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
            'category' => $this->category,
            'dosage' => $this->dosage,
           
        ];
    }

    public function orders()
{
    return $this->belongsToMany(Order::class, 'order_drug')
                ->withPivot('quantity', 'price')
                ->withTimestamps();
}

}
