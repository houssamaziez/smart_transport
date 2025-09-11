<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function confirmReceive(Request $request): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function assignDriver(Request $request): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}



