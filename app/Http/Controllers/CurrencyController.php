<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetVisitorCurrency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    // Session-only choice; the cookie still decides the default on the next visit
    public function switchCurrency(Request $request)
    {
        $request->validate([
            'currency' => ['required', Rule::exists('currency_types', 'code')->where('is_active', true)],
        ]);

        $request->session()->put(SetVisitorCurrency::SESSION_KEY, $request->currency);

        return redirect()->back();
    }
}
