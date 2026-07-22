<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    // جلب جميع العملات
    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => Currency::all()
        ]);
    }

    // حفظ وتحديث أسعار العملات
    public function updateRates(Request $request)
    {
        $rates = $request->input('rates', []); // متوقع استقبال مصفوفة تحتوي على معرف العملة والسعر الجديد

        foreach ($rates as $item) {
            Currency::where('id', $item['id'])->update([
                'rate' => $item['rate']
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث أسعار العملات بنجاح',
            'data' => Currency::all()
        ]);
    }
}