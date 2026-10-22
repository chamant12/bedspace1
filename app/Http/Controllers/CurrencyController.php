<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetVisitorCurrency;
use App\Models\CurrencyType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    // Changes only the currency prices are displayed in; the cookie still decides the price list
    public function switchDisplay(Request $request)
    {
        $request->validate([
            'currency' => ['required', Rule::exists('currency_types', 'code')->where('is_active', true)],
        ]);

        $request->session()->put(SetVisitorCurrency::DISPLAY_SESSION_KEY, $request->currency);

        return redirect()->back();
    }
}
