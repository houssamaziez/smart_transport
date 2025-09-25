<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MessageSent;
use Exception;

class ChatController extends Controller
{
    // صفحة الدردشة
    public function index()
    {
        // تأكد أن resources/views/chat.blade.php موجود
        return view('chat');
    }

    // إرسال رسالة وبثها عبر Pusher
    public function sendMessage(Request $request)
    {
        try {
            $message = $request->input('message');

            if (!$message) {
                return response()->json([
                    'error' => 'Message is required'
                ], 422);
            }

            // إذا كنت تختبر على نفس المتصفح، احذف toOthers()
            broadcast(new MessageSent($message)) ->toOthers();

            // الرد على المرسل مباشرة
            return response()->json([
                'status'  => 'Message sent successfully!',
                'message' => $message
            ]);

        } catch (Exception $e) {
            // طباعة أي خطأ في السيرفر لتسهيل التصحيح
            return response()->json([
                'error'   => 'Failed to send message',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
