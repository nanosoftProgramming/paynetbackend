<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // جلب إشعارات المستخدم الحالي
    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id;
            
            $notifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
->get();
            return returnMessage(true, 'Notifications fetched successfully', $notifications, 'success');
        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }

    // تحديد إشعار كـ مقروء
    public function markAsRead(Request $request, $id)
    {
        try {
            $notification = Notification::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$notification) {
                return returnMessage(false, 'Notification not found', null, 'not_found');
            }

            $notification->update([
                'read_at' => now(),
            ]);

            return returnMessage(true, 'Notification marked as read', $notification, 'success');
        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }

    // تحديد كل الإشعارات كمقروءة
    public function markAllAsRead(Request $request)
    {
        try {
            Notification::where('user_id', $request->user()->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return returnMessage(true, 'All notifications marked as read', null, 'success');
        } catch (\Throwable $th) {
            return returnMessage(false, $th->getMessage(), null, 'server_error');
        }
    }

    // حذف إشعار معين
    public function destroy(Request $request, $id)
    {
        try {
            $notification = Notification::where('id', $id)
                ->where('user_id', $request->user()->id) // التأكد أن الإشعار يخص المستخدم الحالي فقط
                ->first();

            if (!$notification) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'status' => true,
                'message' => 'Notification deleted successfully'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false, 
                'message' => $th->getMessage()
            ], 500);
        }
    }
}