<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Drug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    
    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'items' => 'required|array|min:1',
        'items.*.drug_id' => 'required|exists:drugs,id',
        'items.*.quantity' => 'required|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    $user = Auth::user();
    $itemsWithDetails = [];
    $totalAmount = 0;

    DB::beginTransaction();

    try {
        foreach ($request->items as $item) {
            $drug = Drug::lockForUpdate()->findOrFail($item['drug_id']);
            $quantity = $item['quantity'];

         
            if ($drug->stock === 0) {
                DB::rollBack();
                return response()->json([
                    'message' => "{$drug->name} is out of stock and cannot be ordered."
                ], 400);
            }

        
            if ($drug->stock < $quantity) {
                DB::rollBack();
                return response()->json([
                    'message' => "Insufficient stock for {$drug->name}. Available: {$drug->stock}"
                ], 400);
            }

            
            $drug->stock -= $quantity;
            $drug->save();

           
            InventoryLog::create([
                'drug_id' => $drug->id,
                'user_id' => $user->id,
                'change_type' => 'sale',
                'quantity_changed' => -$quantity,
                'reason' => "Order placed",
            ]);

            $price = $drug->price;
            $subtotal = $price * $quantity;

            $itemsWithDetails[] = [
                'drug_id' => $drug->id,
                'name' => $drug->name,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];

            $totalAmount += $subtotal;
        }

        $order = Order::create([
            'user_id' => $user->id,
            'items' => json_encode($itemsWithDetails),
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Order placed successfully',
            'data' => new OrderResource($order),
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Order failed',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    

public function adminOrders()
{
    $user = Auth::user();

    if ($user->is_role !== 0) {
        return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
    }

    $orders = Order::all();

    return OrderResource::collection($orders);
}

public function userOrders()
{
    $user = Auth::user();

    $orders = Order::where('user_id', $user->id)->get();

    return OrderResource::collection($orders);
}



    public function show($id)
    {
        $user = Auth::user();
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        
        if ($order->user_id !== $user->id && !($user->is_admin ?? false)) {
            return response()->json([
                'message' => 'Unauthorized access to this order'
            ], 403);
        }

        return new OrderResource($order);
    }
}
