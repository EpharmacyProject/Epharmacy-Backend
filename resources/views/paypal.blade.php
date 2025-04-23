<!-- resources/views/paypal.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to PayPal...</title>
</head>
<body>
    <p>Redirecting to PayPal for payment. Please wait...</p>

    <form id="paypalForm" action="{{ route('paypal.process') }}" method="POST">
        @csrf
        <input type="hidden" name="amount" value="{{ $amount }}"> {{-- Replace with dynamic value --}}
    </form>

    <script>
        // Auto-submit the form
        window.onload = function () {
            document.getElementById('paypalForm').submit();
        };
    </script>
</body>
</html>

