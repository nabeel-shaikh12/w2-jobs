<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CurrencyController extends Controller
{
    public $currencies;
    public $path;

    public function __construct()
    {
        $this->middleware('permission:currency');

        $this->path = base_path('database/json/currencies.json');
        if (file_exists($this->path)) {
            $this->currencies = json_decode(file_get_contents($this->path), true);
        } else {
            $this->currencies = [];
        }
    }

    public function index()
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Create a Currency'),
                'url' => '#',
                'target' => '#addNewCurrencyDrawer',
            ]
        ];

        return Inertia::render('Admin/Currency/Index', [
            'currencies' => $this->currencies,
            'buttons' => $buttons,
            'segments' => $segments,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|max:20',
        ]);
        $newCurrency = [
            "id" => !empty($this->currencies) ?  max(array_keys($this->currencies)) + 1 : 1,
            "code" => $request->input('code'),
            "name" => $request->input('name')
        ];
        $this->currencies[$newCurrency['id']] = $newCurrency;

        file_put_contents($this->path, json_encode($this->currencies, JSON_PRETTY_PRINT));

        return to_route('admin.currency.index');
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|max:20',
        ]);

        if (isset($this->currencies[$id])) {
            $this->currencies[$id]['name'] = $request->input('name');
            $this->currencies[$id]['code'] = $request->input('code');

            file_put_contents($this->path, json_encode($this->currencies, JSON_PRETTY_PRINT));
        }

        return to_route('admin.currency.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (isset($this->currencies[$id])) {
            unset($this->currencies[$id]);
            file_put_contents($this->path, json_encode($this->currencies, JSON_PRETTY_PRINT));
        }
        return to_route('admin.currency.index');
    }
}
