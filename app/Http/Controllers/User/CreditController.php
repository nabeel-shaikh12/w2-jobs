<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CreditLog;
use App\Models\Gateway;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CreditController extends Controller
{
    public function __invoke(Request $request)
    {
        $creditLogs = CreditLog::query()
            ->with(['user:id,name,created_at', 'gateway'])
            ->when($request->filled('status'), function ($query) {
                $status = match (request('status')) {
                    'complete' => 1,
                    default => 0,
                };
                $query->where('status', $status);
            })
            ->orderBy('created_at', in_array($request->order, ['desc', 'asc']) ? $request->order : 'desc')
            ->where('user_id', auth()->id())
            ->paginate(10);
        $credit_fee = get_option('per_credit_fee');
        $gateways = Gateway::where('status', 1)->select('id', 'name', 'currency', 'logo', 'charge', 'multiply', 'min_amount', 'max_amount', 'is_auto', 'image_accept', 'test_mode', 'status', 'phone_required', 'comment')->get();

        return Inertia::render('User/Credit', [
            'creditLogs' => $creditLogs,
            'credit_fee' => $credit_fee,
            'gateways' => $gateways,
        ]);
    }
}
