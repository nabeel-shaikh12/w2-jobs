<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Cache;
use App\Models\Option;

class CreditController extends Controller
{
    public function index(Request $request)
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-credit-card"></i>&nbsp&nbsp' . __('Credit Fee'),
                'url' => '#',
                'target' => '#updateCreditFeeDrawer',

            ],
            [
                'name' => '<i class="fa fa-wallet"></i>&nbsp&nbsp' . __('Word Credit Fee'),
                'url' => '#',
                'target' => '#updateWordCreditFeeDrawer',

            ],
        ];
        $creditLogs = CreditLog::query();

        if (!empty($request->search)) {
            if ($request->type == 'email') {
                $creditLogs = $creditLogs->whereHas('user', function ($q) {
                    return $q->where('email', request('search'));
                });
            } else {
                $creditLogs = $creditLogs->where($request->type, 'LIKE', '%' . $request->search . '%');
            }
        }
        $creditLogs = $creditLogs
            ->with(['user:id,name,created_at,role', 'gateway'])
            ->paginate(10);

        $totalCreditLog = CreditLog::query()->count();
        $activeCreditLog = CreditLog::query()->where('status', 1)->count();
        $inactiveCreditLog = CreditLog::query()->where('status', 0)->count();
        $per_credit_fee = get_option('per_credit_fee');
        $per_word_credit = get_option('per_word_credit');

        $type = $request->type ?? 'email';
        return Inertia::render('Admin/Credits/Index', [
            'creditLogs' => $creditLogs,
            'per_credit_fee' => $per_credit_fee,
            'per_word_credit' => $per_word_credit,
            'buttons' => $buttons,
            'segments' => $segments,
            'totalCreditLog' => $totalCreditLog ?? [],
            'activeCreditLog' => $activeCreditLog ?? [],
            'inactiveCreditLog' => $inactiveCreditLog ?? [],
            'request' => $request,
            'type' => $type,
        ]);
    }

    public function update(Request $request, $id)
    {
        $creditLog = CreditLog::findOrFail($id);

        $request->validate([
            'credits' => 'required',
        ]);
        if ($request->status == 1 && $creditLog->status == 0 || $creditLog->status == 2) {
            $user = User::query()->findOrFail($creditLog->user_id);
            $user->increment('credits', $request->credits ?? $creditLog->credits);
        }
        $creditLog->update([
            'credits' => $request->credits,
            'status' => $request->status ?? $creditLog->status,
        ]);

        return back();
    }

    public function updateCreditFee(Request $request)
    {
        $option = Option::where('key', 'per_credit_fee')->first();
        $option->value = $request->per_credit_fee;
        $option->save();

        Cache::forget('per_credit_fee');

        return back();
    }
    public function updateWordCredit(Request $request)
    {
        $option = Option::where('key', 'per_word_credit')->first();
        $option->value = $request->per_word_credit;
        $option->save();

        Cache::forget('per_word_credit');

        return back();
    }
}
