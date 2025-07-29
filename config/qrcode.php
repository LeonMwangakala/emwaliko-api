<?php

return [

    /*
    |--------------------------------------------------------------------------
    | QR Code Backend
    |--------------------------------------------------------------------------
    |
    | This option controls the default QR code backend that is used to
    | generate QR codes. You may set this to any of the backends
    | you wish as it may suit your application.
    |
    | Supported: "imagick", "svg", "eps"
    |
    */

    'backend' => env('QR_CODE_BACKEND', 'imagick'),

    /*
    |--------------------------------------------------------------------------
    | QR Code Backend Options
    |--------------------------------------------------------------------------
    |
    | Here you may configure the QR code backend options. You may set
    | any of the options that are supported by the backend.
    |
    */

    'backends' => [

        'imagick' => [
            'format' => 'png',
            'size' => 300,
            'margin' => 10,
            'error_correction' => 'M',
        ],

        'svg' => [
            'format' => 'svg',
            'size' => 300,
            'margin' => 10,
            'error_correction' => 'M',
        ],

        'eps' => [
            'format' => 'eps',
            'size' => 300,
            'margin' => 10,
            'error_correction' => 'M',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | QR Code Style
    |--------------------------------------------------------------------------
    |
    | Here you may configure the QR code style options. You may set
    | any of the options that are supported by the backend.
    |
    */

    'style' => [
        'foreground_color' => '#000000',
        'background_color' => '#FFFFFF',
    ],

]; 