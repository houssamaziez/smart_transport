<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthController extends Controller
{
    /**
     * ✅ تسجيل مستخدم جديد
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'      => 'required|string|max:255',
                'phone'     => 'required|string|max:20|unique:users,phone',
                'email'     => 'nullable|email|unique:users,email',
                'password'  => 'required|string|min:6|confirmed',
                'region'    => 'required|string|max:100',
                'role'      => 'required|in:customer,driver,station,admin',
            ]);

            $user = User::create([
                'name'     => $validated['name'],
                'phone'    => $validated['phone'],
                'email'    => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'role'     => $validated['role'],
                'region'   => $validated['region'],
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status'  => true,
                'message' => 'Registration successful',
                'data'    => [
                    'token' => $token,
                    'user'  => $user,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Registration failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ تسجيل الدخول (يدعم email أو phone)
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login'    => 'required|string', // ← يمكن أن يكون email أو phone
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // البحث بالبريد أو رقم الهاتف
        $user = User::where('email', $request->login)
                    ->orWhere('phone', $request->login)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Login successful',
            'data'    => [
                'token' => $token,
                'user'  => $user,
            ],
        ], 200);
    }

    /**
     * ✅ عرض بيانات المستخدم الحالي
     */
public function profile(Request $request)
{
    try {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not authenticated',
                'data'    => null,
            ], 401);
        }

        return response()->json([
            'status'  => true,
            'message' => 'User profile fetched successfully',
            'data'    => [
                'id'             => $user->id,
                'role'           => $user->role,
                'name'           => $user->name,
                'email'          => $user->email,
                'phone'          => $user->phone,
                'avatar'         => $user->avatar,
                'status'         => $user->status,
                'latitude'       => $user->latitude,
                'longitude'      => $user->longitude,
                'wallet_balance' => $user->wallet_balance,
                'vehicle_type'   => $user->vehicle_type,
                'license_number' => $user->license_number,
                'last_seen_at'   => $user->last_seen_at,
                'fcm_token'      => $user->fcm_token,
                'email_verified_at' => $user->email_verified_at,
                'region'         => $user->region,
                'created_at'     => $user->created_at,
                'updated_at'     => $user->updated_at,
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong while fetching profile',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * ✅ تسجيل الخروج من جميع الأجهزة
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logged out from all devices',
        ], 200);
    }

    /**
     * ✅ تحديث بيانات المستخدم
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'   => 'nullable|string|max:255',
            'email'  => 'nullable|email|unique:users,email,' . $user->id,
            'phone'  => 'nullable|string|min:8|max:15|unique:users,phone,' . $user->id,
            'region' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update($request->only('name', 'email', 'phone', 'region'));

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully',
            'data'    => $user,
        ], 200);
    }

    /**
     * ✅ تغيير كلمة المرور
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'password'     => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Old password is incorrect',
            ], 401);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Password changed successfully',
        ], 200);
    }
}
