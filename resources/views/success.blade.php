@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="text-success">Payment Successful</h2>
    <p><strong>Order ID:</strong> {{ $order->id }}</p>
    <p><strong>Payment ID:</strong> {{ $payment->payment_id }}</p>
    <p><strong>Payer Email:</strong> {{ $payment->payer_email }}</p>
    <p><strong>Amount:</strong> {{ $payment->amount }} {{ $payment->currency }}</p>
    <p><strong>Status:</strong> {{ $payment->payment_status }}</p>
</div>
@endsection
