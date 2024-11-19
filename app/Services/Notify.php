<?php

namespace App\Services;

// @see https://github.com/Maronato/vue-toastification

class Notify
{
    public static $toasts = [];

    private static function push(string $alertType, string $alertMessage, ?array $options = [])
    {
        array_push(self::$toasts, [
            'type' => $alertType,
            'message' => $alertMessage ?? '',
            'options' => $options,
        ]);
    }

    public static function success(string $alertMessage, ?array $options = [])
    {
        self::push('success', $alertMessage, $options);
    }

    public static function info(string $alertMessage, ?array $options = [])
    {
        self::push('info', $alertMessage, $options);
    }

    public static function warning(string $alertMessage, ?array $options = [])
    {
        self::push('warning', $alertMessage, $options);
    }

    public static function error(string $alertMessage, ?array $options = [])
    {
        self::push('error', $alertMessage, $options);
    }

    public static function messages()
    {
        return self::$toasts;
    }
}
