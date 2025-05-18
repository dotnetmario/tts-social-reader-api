<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BaseController extends Controller
{
    /**
     * Send a successful JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function sendSuccessResponse($data = null, int $statusCode = 200, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Send an error JSON response.
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function sendErrorResponse(int $statusCode = 400, string $message = 'An error occurred', array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Handle validation exceptions and return a JSON response.
     *
     * @param ValidationException $exception
     * @return JsonResponse
     */
    protected function handleValidationException(ValidationException $exception): JsonResponse
    {
        return $this->sendErrorResponse(
            422,
            'Validation failed',
            $exception->errors()
        );
    }

    /**
     * Validate the request and automatically return validation errors in JSON format.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $rules
     * @param array $messages
     * @param array $attributes
     * @return array Validated data
     */
    protected function validateRequest($request, array $rules, array $messages = [], array $attributes = []): array
    {
        try {
            return $request->validate($rules, $messages, $attributes);
        } catch (ValidationException $exception) {
            // throw $exception;
            throw new ValidationException(
                $exception->validator,
                $this->sendErrorResponse(
                    422,
                    'Validation failed',
                    $exception->errors()
                )
            );
        }
    }
}
