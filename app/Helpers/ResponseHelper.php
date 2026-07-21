<?php

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns a JSON response with status, message, and data
 *
 * @param bool $status Whether the operation was successful
 * @param string|null $message Response message
 * @param mixed $data Response data
 * @param string $statusCode HTTP status code identifier
 * @return JsonResponse
 */
function returnMessage(bool $status = false, ?string $message = null, mixed $data = null, string $statusCode = 'ok'): JsonResponse
{
    return new JsonResponse(
        [
            'status' => $status,
            'message' => $message,
            'data' => $data
        ],
        getStatusCode($statusCode)
    );
}

/**
 * Returns a JSON response with status, message, and errors
 *
 * @param bool $status Whether the operation was successful
 * @param string|null $message Response message
 * @param mixed $errors Response errors
 * @param string $statusCode HTTP status code identifier
 * @return JsonResponse
 */
function returnValidationMessage(bool $status = false, ?string $message = null, mixed $errors = null, string $statusCode = 'unprocessable_entity'): JsonResponse
{
    return new JsonResponse(
        [
            'status' => $status,
            'message' => $message,
            'errors' => $errors
        ],
        getStatusCode($statusCode)
    );
}

/**
 * Returns a JSON response for unauthorized access
 *
 * @param string|null $message Custom unauthorized message
 * @param mixed $errors Additional errors to include in response
 * @return JsonResponse
 */
function returnUnauthorizedMessage(bool $status = false, ?string $message = null, mixed $errors = null): JsonResponse
{
    return new JsonResponse(
        [
            'status' => $status,
            'message' => $message ?? 'Unauthorized access',
            'errors' => $errors
        ],
        getStatusCode('unauthorized')
    );
}

/**
 * Get HTTP status code from status type
 *
 * @param string $type Status code identifier
 * @return int HTTP status code
 */
function getStatusCode(string $type = 'ok'): int
{
    return allStatusCode()[strtolower($type)] ?? Response::HTTP_OK;
}

/**
 * Returns an array of common HTTP status codes with their descriptive keys
 *
 * @return array<string, int>
 */
function allStatusCode(): array
{
    return [
        "ok" => Response::HTTP_OK, // 200
        "created" => Response::HTTP_CREATED, // 201
        "accepted" => Response::HTTP_ACCEPTED, // 202
        "non_authoritative_information" => Response::HTTP_NON_AUTHORITATIVE_INFORMATION, // 203
        "no_content" => Response::HTTP_NO_CONTENT, // 204
        "moved" => Response::HTTP_MOVED_PERMANENTLY, // 301
        "found" => Response::HTTP_FOUND, // 302
        "see_other" => Response::HTTP_SEE_OTHER, // 303
        "not_modified" => Response::HTTP_NOT_MODIFIED, // 304
        "temporary_redirect" => Response::HTTP_TEMPORARY_REDIRECT, // 307
        "bad_request" => Response::HTTP_BAD_REQUEST, // 400
        "unauthorized" => Response::HTTP_UNAUTHORIZED, // 401
        "forbidden" => Response::HTTP_FORBIDDEN, // 403
        "not_found" => Response::HTTP_NOT_FOUND, // 404
        "method_not_allowed" => Response::HTTP_METHOD_NOT_ALLOWED, // 405
        "not_acceptable" => Response::HTTP_NOT_ACCEPTABLE, // 406
        "precondition_failed" => Response::HTTP_PRECONDITION_FAILED, // 412
        "unsupported_media_type" => Response::HTTP_UNSUPPORTED_MEDIA_TYPE, // 415
        "unprocessable_entity" => Response::HTTP_UNPROCESSABLE_ENTITY, // 422
        "server_error" => Response::HTTP_INTERNAL_SERVER_ERROR, // 500
        "not_implemented" => Response::HTTP_NOT_IMPLEMENTED, // 501
    ];
}


//Pagination Response Helper
function getCaseCollection($builder, array $data)
{
    if ($data['paginated'] ?? null) {
        return $builder->paginate($data['paginated'] ?? 20);
    }
    return $builder->get();
}
