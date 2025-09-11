<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // ✅ تسجيل مستخدم جديد
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'phone'     => 'required|string|max:20|unique:users,phone',
            'email'     => 'nullable|email|unique:users,email',
            'password'  => 'required|string|min:6|confirmed',
            'region'    => 'required|string|max:100',
            'role'      => 'required|in:customer,driver,station,admin', // ✅ الدور
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'phone'    => $validated['phone'],
            'email'    => $validated['email'] ?? null,
            'password' => bcrypt($validated['password']),
            'role'     => $validated['role'],   // ✅ إضافة الدور
            'region'   => $validated['region'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Registration successful',
            'token'   => $token,
            'user'    => $user
        ], 201);
    }



    // ✅ تسجيل الدخول
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid email or password'
            ], 401);
        }

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user,
        ], 200);
    }

    // ✅ عرض بيانات المستخدم الحالي
    public function profile(Request $request)
    {
        return response()->json([
            'status'  => true,
            'message' => 'User profile fetched successfully',
            'user'    => $request->user(),
        ], 200);
    }

    // ✅ تسجيل الخروج من الجلسة الحالية
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    // ✅ تسجيل الخروج من جميع الجلسات
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logged out from all devices'
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'   => 'nullable|string|max:255',
            'email'  => 'nullable|email|unique:users,email,' . $user->id,
            'phone'  => 'nullable|string|min:8|max:15|unique:users,phone,' . $user->id,
            'region' => 'nullable|string|max:100', // ← أضفنا فلترة المنطقة
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // تحديث البيانات بما فيها region إن وجدت
        $user->update($request->only('name', 'email', 'phone', 'region'));

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully',
            'user'    => $user,
        ], 200);
    }


    // ✅ تغيير كلمة المرور
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'password'     => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Old password is incorrect'
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
