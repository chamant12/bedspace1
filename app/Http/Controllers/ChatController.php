<?php

namespace App\Http\Controllers;

use App\Chat\Receptionist;
use App\Models\ChatLog;
use App\Support\BusinessDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Website chat widget backend. The conversation is kept in the visitor's
 * session (server side), so a visitor cannot inject earlier turns.
 * Daily limits per visitor and for the whole site cap the API spend.
 */
class ChatController extends Controller
{
    const SESSION_KEY = 'chat_history';

    public function __construct(private Receptionist $receptionist)
    {
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'enabled' => $this->receptionist->isEnabled(),
            'history' => $request->session()->get(self::SESSION_KEY, []),
        ]);
    }

    public function message(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:1000']]);
        $question = trim($data['message']);
        $visitor = hash('sha256', $request->session()->getId());
        $started = microtime(true);

        $history = $request->session()->get(self::SESSION_KEY, []);
        $currency = $request->attributes->get('visitorCurrency')?->code ?? config('bedspace.local_currency');

        if ($limited = $this->overLimit($visitor)) {
            $result = ['reply' => $limited, 'outcome' => 'limited', 'usage' => [], 'tools' => [], 'model' => null];
        } else {
            $result = $this->receptionist->reply(
                array_slice($history, -config('bedspace.chatbot.history_messages')), $question, $currency);
        }

        if ($result['outcome'] === 'answered') {
            $history[] = ['role' => 'user', 'content' => $question];
            $history[] = ['role' => 'assistant', 'content' => $result['reply']];
            $request->session()->put(self::SESSION_KEY, array_slice($history, -40));
        }

        ChatLog::create([
            'visitor' => $visitor,
            'question' => $question,
            'answer' => $result['reply'],
            'model' => $result['model'],
            'tools_used' => $result['tools'] ? mb_substr(implode(',', $result['tools']), 0, 500) : null,
            'input_tokens' => $result['usage']['input'] ?? 0,
            'output_tokens' => $result['usage']['output'] ?? 0,
            'cache_read_tokens' => $result['usage']['cache_read'] ?? 0,
            'cache_write_tokens' => $result['usage']['cache_write'] ?? 0,
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            'outcome' => $result['outcome'],
            'created_at' => now(),
        ]);

        return response()->json(['reply' => $result['reply'], 'enabled' => $this->receptionist->isEnabled()]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);
        return response()->json(['ok' => true]);
    }

    // Message to show when a daily limit is reached, or null
    private function overLimit(string $visitor): ?string
    {
        if (!$this->receptionist->isEnabled()) {
            return null; // the "coming soon" reply costs nothing
        }

        $day = BusinessDate::today();
        $visitorKey = "chatbot:visitor:$visitor:$day";
        $siteKey = "chatbot:site:$day";
        Cache::add($visitorKey, 0, now()->addDay());
        Cache::add($siteKey, 0, now()->addDay());

        if (Cache::increment($siteKey) > config('bedspace.chatbot.max_messages_per_day')) {
            return "Our virtual receptionist is very busy right now. Please search for a stay at ".url('/search')
                ." or find your booking at ".url('/booking/find').".";
        }
        if (Cache::increment($visitorKey) > config('bedspace.chatbot.max_messages_per_visitor_per_day')) {
            return "You have reached today's limit of messages. You can still search at ".url('/search')
                ." or find your booking at ".url('/booking/find').".";
        }
        return null;
    }
}
