<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;

class SupportController extends Controller
{
    /**
     * ✅ فتح تذكرة جديدة
     */
    public function create(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Support ticket created successfully',
            'ticket' => $ticket
        ], 201);
    }

    /**
     * ✅ عرض كل تذاكر المستخدم الحالي
     */
    public function myTickets(Request $request)
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'tickets' => $tickets
        ]);
    }

    /**
     * ✅ عرض تفاصيل تذكرة
     */
    public function show($id, Request $request)
    {
        $ticket = SupportTicket::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'status'  => false,
                'message' => 'Ticket not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'ticket' => $ticket
        ]);
    }
}
