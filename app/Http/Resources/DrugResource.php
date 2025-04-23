<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'brand'       => $this->brand,
            'price'       => $this->price,
            'description' => $this->description,
            'category'    => $this->category,
            'stock'       => $this->stock,
            'dosage'      => $this->dosage,
            'expires_at'  => $this->expires_at,
           
        ];
        
    }
}
