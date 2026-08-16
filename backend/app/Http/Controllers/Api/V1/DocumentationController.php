<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="ShopFlow API",
 *     version="1.0.0",
 *     description="Production-style e-commerce & inventory management REST API"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Local development server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum"
 * )
 */
class DocumentationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/docs/openapi.json",
     *     summary="Get OpenAPI specification",
     *     @OA\Response(
     *         response=200,
     *         description="OpenAPI JSON specification"
     *     )
     * )
     */
    public function openapi(): JsonResponse
    {
        $openapi = \OpenApi\Generator::scan([
            app_path('Http/Controllers/Api/V1'),
        ]);

        return response()->json($openapi, 200, [], JSON_PRETTY_PRINT);
    }
}
