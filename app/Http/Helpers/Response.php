<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\Log;

class Response
{

    public static function error($errors, $data = null, $status = 400)
    {
        $responseData = [
            'message'   => [
                'error'    => $errors,
            ],
            'data'      => $data,
            'type'          => "error",
        ];

        return self::jsonResponse($responseData, $status);
    }

    public static function success($success, $data = null, $status = 200)
    {
        $responseData = [
            'message'       => [
                'success'   => $success,
            ],
            'data'          => $data,
            'type'          => "success",
        ];

        return self::jsonResponse($responseData, $status);
    }

    public static function warning($warning, $data = null, $status = 400)
    {
        $responseData = [
            'message'       => [
                'error'     => $warning,
            ],
            'data'          => $data,
            'type'          => "warning",
        ];

        return self::jsonResponse($responseData, $status);
    }

    /**
     * Create a properly formatted JSON response with UTF-8 support
     *
     * @param array $data
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    private static function jsonResponse($data, $status = 200)
    {
        // Pre-encode to check for errors and get accurate byte size
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($json === false) {
            Log::error('JSON Encoding Error: ' . json_last_error_msg(), [
                'data_keys' => array_keys($data),
            ]);

            // Fallback response
            return response()->json([
                'message' => ['error' => ['Failed to encode response']],
                'type' => 'error',
            ], 500, ['Content-Type' => 'application/json; charset=UTF-8']);
        }

        // Log warning if response is unusually large (>50KB)
        $byteSize = strlen($json);
        if ($byteSize > 51200) {
            Log::warning('Large JSON Response', [
                'byte_size' => $byteSize,
                'kb_size' => round($byteSize / 1024, 2),
            ]);
        }

        return response($json, $status, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Length' => $byteSize,
        ]);
    }
}
