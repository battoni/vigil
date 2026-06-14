<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data = null, $extraData = null, $status = 200, $cookie = null)
    {
        $responseData = [];

        if ($data !== null) {
            $responseData = ['data' => $data];
        }

        if ($extraData) {
            $responseData = array_merge($responseData, $extraData);
        }

        $response = response()->json(
            data: $responseData,
            status: $status,
            options: JSON_PRETTY_PRINT
        );

        if ($cookie) {
            $response = $response->withCookie(cookie: $cookie);
        }

        return $response;
    }

    public static function created($data = null, $extraData = null)
    {
        return self::success(data: $data, extraData: $extraData, status: 201);
    }

    public static function error($message, $extraData = null, $status = 400)
    {
        $responseData = [
            'data' => null,
            'error' => $message,
            'status_code' => $status,
        ];

        if ($extraData) {
            $responseData = array_merge($responseData, $extraData);
        }

        return response()->json(
            data: $responseData,
            status: $status,
            options: JSON_PRETTY_PRINT
        );
    }

    public static function badRequest($message, $extraData = null)
    {
        return self::error(message: $message, status: 400, extraData: $extraData);
    }

    public static function unauthorized($message, $extraData = null)
    {
        return self::error(message: $message, status: 401, extraData: $extraData);
    }

    public static function forbidden($message, $extraData = null)
    {
        return self::error(message: $message, status: 403, extraData: $extraData);
    }

    public static function notFound($message, $extraData = null)
    {
        return self::error(message: $message, status: 404, extraData: $extraData);
    }

    public static function conflict($message, $extraData = null)
    {
        return self::error(message: $message, status: 409, extraData: $extraData);
    }

    public static function internalError($message, $extraData = null)
    {
        return self::error(message: $message, status: 500, extraData: $extraData);
    }

    public static function serviceUnavailable($message, $extraData = null)
    {
        return self::error(message: $message, status: 503, extraData: $extraData);
    }
}
