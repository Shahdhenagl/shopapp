<?php

namespace App\Traits;

trait ApiResponse
{
    // رسالة النجاح
    public function successResponse($data = null, $message = "تم بنجاح", $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }
    public function successMessage($message = "تم بنجاح", $code = 200)
{
    return response()->json([
        'status' => 'success',
        'message' => $message,
    ], $code);
}
    // رسالة الفشل / الخطأ
    public function errorResponse($message = "حدث خطأ", $code = 400, $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $code);
    }


    public function codeSentResponse($message = "تم إرسال الكود بنجاح")
{
    return response()->json([
        'status' => 'success',
        'message' => $message,
    ], 200);
}
}
