<?php

namespace App\Http\Controllers\Admin;

use App\Chat\Receptionist;
use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use App\Services\IncomeReports;
use App\Support\BusinessDate;
use Illuminate\Http\Request;

// Chatbot usage and estimated Claude API cost per month, plus recent conversations
class ChatbotController extends Controller
{
    public function index(Request $request, IncomeReports $reports, Receptionist $receptionist)
    {
        $month = $reports->isValidMonth($request->month) ? $request->month : substr(BusinessDate::today(), 0, 7);
        [$from, $to] = $reports->monthRange($month);

        $byModel = ChatLog::where('created_at', '>=', $from)->where('created_at', '<', $to)
            ->selectRaw("model, COUNT(*) as messages, SUM(outcome = 'answered') as answered,
                SUM(input_tokens) as input_tokens, SUM(output_tokens) as output_tokens,
                SUM(cache_read_tokens) as cache_read_tokens, SUM(cache_write_tokens) as cache_write_tokens,
                AVG(duration_ms) as avg_ms, COUNT(DISTINCT visitor) as visitors")
            ->groupBy('model')
            ->get()
            ->map(function ($row) {
                $price = config('bedspace.chatbot.prices.'.$row->model);
                // Cache writes cost 1.25x the input price (5-minute cache)
                $row->cost_usd = $price ? round(
                    ($row->input_tokens * $price['input'] + $row->cache_write_tokens * $price['input'] * 1.25
                     + $row->cache_read_tokens * $price['cache_read'] + $row->output_tokens * $price['output']) / 1_000_000, 2) : null;
                return $row;
            });

        $recent = ChatLog::orderByDesc('id')->take(50)->get();

        return view('admin.chatbot', [
            'month' => $month,
            'byModel' => $byModel,
            'recent' => $recent,
            'enabled' => $receptionist->isEnabled(),
            'model' => config('bedspace.chatbot.model'),
        ]);
    }
}
