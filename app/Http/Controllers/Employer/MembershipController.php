<?php

namespace App\Http\Controllers\Employer;

use Storage;
use App\Models\Plan;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Order;
use App\Models\Gateway;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Uploader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;


class MembershipController extends Controller
{
    use Uploader;

    public function index()
    {
        $user = User::with('plan')->findOrFail(auth()->id());
        $plans = Plan::query()
            ->where('price', '>', 0)->where('status', 1)
            ->get();

        $orders = Order::with('plan')->whereUserId(auth()->id())->latest()->paginate();
        return Inertia::render('Employer/Membership', compact('user', 'plans', 'orders'));
    }

    public function payment($id)
    {
        $plan = Plan::where('status', 1)->where('price', '>', 0)->where('id', $id)->first();

        if (empty($plan)) {
            Session::flash('saas_error', __('Please select a plan from here'));
            return redirect('/user/subscription');
        }

        if ($plan->price <= 0) {
            Session::flash('saas_error', __('Please select a plan from here'));

            return redirect('/user/subscription');
        }

        $gateways = Gateway::where('status', 1)->select('id', 'name', 'currency', 'logo', 'charge', 'multiply', 'min_amount', 'max_amount', 'is_auto', 'image_accept', 'test_mode', 'status', 'phone_required', 'comment')->get();
        $tax      = get_option('tax', true);

        $tax      = $tax > 0 ? ($plan->price / 100) * $tax : 0;
        $total    = (float)$tax + $plan->price;
        $invoice_data = get_option('invoice_data', true);

        Session::forget('payment_info');
        Session::forget('call_back');
        Session::forget('plan_id');

        $error = Session::has('error');
        $minMax = Session::has('min-max');

        return Inertia::render('Employer/Payment', [
            'plan' => $plan,
            'gateways' => $gateways,
            'tax' => $tax,
            'total' => $total,
            'invoice_data' => $invoice_data,
            'error' => $error,
            'minMax' => $minMax,
            'minMaxMessage' => $minMax ? Session::get('min-max') : '',
            'user' => auth()->user(),
            'logo' => get_option('primary_data', true)->logo ?? ''
        ]);
    }

    public function subscribe(Request $request)
    {
        $plan     = Plan::where('status', 1)->where('price', '>', 0)->findOrFail($request->plan_id);

        $gateway  = Gateway::where('status', 1)->findOrFail($request->gateway_id);
        $tax      = get_option('tax', true);
        $tax      = $tax > 0 ? ($plan->price / 100) * $tax : 0;
        $total    = (float)$tax + $plan->price;
        $payable  = $total * $gateway->multiply + $gateway->charge;

        if ($gateway->min_amount > $payable) {
            Session::flash('min-max', __('The minimum transaction amount is ' . $gateway->min_amount));
            return redirect()->back();
        }
        if ($gateway->max_amount != -1) {
            if ($gateway->max_amount < $payable) {
                Session::flash('min-max', __('The maximum transaction amount is ' . $gateway->max_amount));
                return redirect()->back();
            }
        }

        if ($gateway->is_auto == 0) {
            $request->validate([
                'manualPayment.comment' => ['required', 'string', 'max:500'],
                'manualPayment.image' => ['required', 'image', 'max:2048'], // 2MB
            ]);

            $payment_data['comment'] = $request->input('manualPayment.comment');
            $image = $this->saveFile($request, 'manualPayment.image');
            $payment_data['screenshot'] = $image;
            $payment_data['comment'] = $request->input('manualPayment.comment');
        }

        Session::put('plan_id', $plan->id);

        $payment_data['currency']   = $gateway->currency ?? 'USD';
        $payment_data['email']      = Auth::user()->email;
        $payment_data['name']       = Auth::user()->name;
        $payment_data['phone']      = $request->phone;
        $payment_data['billName']   = 'Plan Name: ' . $plan->title;
        $payment_data['amount']     = $total;
        $payment_data['test_mode']  = $gateway->test_mode;
        $payment_data['charge']     = $gateway->charge ?? 0;
        $payment_data['pay_amount'] =  str_replace(',', '', number_format($payable));
        $payment_data['getway_id']  = $gateway->id;
        $callback['success'] = url('employer/memberships/plan/success');
        $callback['fail']    = url('employer/memberships/plan/failed');
        Session::put('call_back', $callback);

        if (!empty($gateway->data)) {
            foreach (json_decode($gateway->data ?? '') ?? [] as $key => $info) {
                $payment_data[$key] = $info;
            };
        }

        return $gateway->namespace::make_payment($payment_data);
    }

    public function status($status)
    {

        abort_if(!Session::has('call_back') || !Session::has('plan_id'), 404);

        return $status == 'success' ? $this->success() : $this->failed();
    }

    public function success()
    {
        abort_if(!Session::has('payment_info'), 404);

        $paymentInfo = Session::get('payment_info');

        Session::forget('payment_info');
        Session::forget('call_back');


        $plan = Plan::where('status', 1)->where('price', '>', 0)->findOrFail(Session::get('plan_id'));
        Session::forget('plan_id');

        $redirectUrl = '/employer/memberships';

        if ($paymentInfo['status'] == 1) {
            $user = User::findOrFail(auth()->id());
            $user->plan = $plan->data;
            $user->credits = $user->credit+$plan->data['ai_credits'] ?? 0;
            $user->plan_id = $plan->id;
            $user->will_expire = now()->addDays($plan->days);
            $user->save();
        }


        $tax      = get_option('tax', true);
        $tax      = $tax > 0 ? ($plan->price / 100) * $tax : 0;

        $order = new Order;
        $order->plan_id     = $plan->id;
        $order->payment_id  = $paymentInfo['payment_id'];
        $order->user_id     = Auth::id();
        $order->gateway_id  = $paymentInfo['getway_id'];
        $order->amount      = $paymentInfo['amount'];
        $order->tax         = $tax;
        $order->status      = $paymentInfo['status'] ?? 1;
        $order->will_expire = now()->addDays($plan->days);
        if (isset($paymentInfo['meta'])) {
            $order->meta    = json_encode($paymentInfo['meta']);
        }
        $order->save();



        $message = $paymentInfo['status'] == 1 ? __('Your subscription payment is complete') : __('Your subscription payment is complete admin will review this payment manually for approval.');

        Notification::create([
            'user_id' => auth()->id(),
            'url' => url('/admin/order/'.$order->id),
            'title' => __('New plan purchased'),
            'is_admin' => 1,
            'comment' => __('An Employer has purchased a plan'),

        ]);

        Session::flash('success', $message);

        if ($paymentInfo['status'] == 1) {
            return redirect($redirectUrl);
        }

        return redirect('employer/memberships');
    }

    public function failed()
    {
        $plan_id = Session::get('plan_id');

        Session::forget('payment_info');
        Session::forget('call_back');
        Session::forget('plan_id');

        Session::flash('error', true);

        return redirect('/employer/memberships/payment/' . $plan_id);
    }
}
