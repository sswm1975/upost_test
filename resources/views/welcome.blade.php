<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>TEST</title>
    </head>
    <body>
        <h1>Test PayPal</h1>
        <form action="{{ route('payment') }}" method="post">
            @csrf
            <button type="submit">Оплатить платеж</button>
        </form>
    </body>
</html>
