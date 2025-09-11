<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function record(Request $request): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}



