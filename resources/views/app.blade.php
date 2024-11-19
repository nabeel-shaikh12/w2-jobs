<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no,  maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>I am here!</title>
    @vite('resources/js/app.js')
    @vite('resources/css/app.css')
    @inertiaHead

    <!-- add custom Javascript by John at 2024-5-15 -->
    <?php 
        $encodehtml = env('TxtAreaInp');
        $decodehtml = base64_decode($encodehtml);
    ?>
    {!! $decodehtml !!}
</head>

<body>
    @routes
    @inertia
</body>

</html>