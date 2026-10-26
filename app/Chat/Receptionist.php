<?php

namespace App\Chat;

use App\Support\BusinessDate;
use Carbon\CarbonImmutable;

/**
 * The website's virtual receptionist: answers guests' questions with Claude,
 * using the read-only guest tools (the same tools the MCP server exposes,
 * run in-process here for speed). It never books, changes or cancels
 * anything; for bookings it gives the guest the right link.
 *
 * Until it is configured (CHATBOT_ENABLED, ANTHROPIC_API_KEY and the
 * anthropic-ai/sdk package) it replies with a "coming soon" message.
 */
class Receptionist
{
    // Models that accept the server-side refusal fallback ("fallbacks": "default")
    const FALLBACK_MODELS = ['claude-opus-5-5', 'claude-opus-5', 'claude-fable-5-1', 'claude-sonnet-5-5'];

    public function __construct(private GuestTools $tools)
    {
    }

    public function isEnabled(): bool
    {
        return config('bedspace.chatbot.enabled')
            && filled(config('bedspace.chatbot.api_key'))
            && class_exists(\Anthropic\Client::class);
    }

    /**
     * @param array<int, array{role: string, content: string}> $history earlier text turns
     * @return array{reply: string, outcome: string, usage: array, tools: string[], model: ?string}
     */
    public function reply(array $history, string $question, string $currency): array
    {
        if (!$this->isEnabled()) {
            return $this->canned(
                "Hello! Our virtual receptionist is coming soon. Meanwhile you can search for a place to stay at "
                .url('/search')." or look up an existing booking at ".url('/booking/find').".", 'disabled');
        }

        $config = config('bedspace.chatbot');
        $client = new \Anthropic\Client(apiKey: $config['api_key']);

        $messages = $history;
        $messages[] = ['role' => 'user', 'content' => $question];

        $usage = ['input' => 0, 'output' => 0, 'cache_read' => 0, 'cache_write' => 0];
        $toolsUsed = [];

        try {
            for ($round = 0; ; $round++) {
                $request = [
                    'model' => $config['model'],
                    'maxTokens' => $config['max_tokens'],
                    // Stable instructions first (cached together with the tool definitions), then today's context
                    'system' => [
                        ['type' => 'text', 'text' => $this->instructions(), 'cacheControl' => ['type' => 'ephemeral']],
                        ['type' => 'text', 'text' => $this->context($currency)],
                    ],
                    'tools' => $this->tools->definitions(),
                    'messages' => $messages,
                    'outputConfig' => ['effort' => $config['effort']],
                ];
                if ($options = $this->requestOptions($config['model'])) {
                    $request['requestOptions'] = $options;
                }
                $response = $client->messages->create(...$request);

                $usage['input'] += (int) ($response->usage->inputTokens ?? 0);
                $usage['output'] += (int) ($response->usage->outputTokens ?? 0);
                $usage['cache_read'] += (int) ($response->usage->cacheReadInputTokens ?? 0);
                $usage['cache_write'] += (int) ($response->usage->cacheCreationInputTokens ?? 0);

                if ($response->stopReason === 'refusal') {
                    return $this->canned("I'm sorry, I can't help with that. I can help you find a place to stay, check prices and availability, "
                        ."or look up your booking.", 'refused', $usage, $toolsUsed, $config['model']);
                }

                if ($response->stopReason !== 'tool_use' || $round >= $config['max_tool_rounds']) {
                    return [
                        'reply' => $this->text($response) ?: "I'm sorry, I couldn't find an answer to that. Could you rephrase your question?",
                        'outcome' => 'answered',
                        'usage' => $usage,
                        'tools' => $toolsUsed,
                        'model' => $config['model'],
                    ];
                }

                // Run every tool Claude asked for, and return all results in one user turn
                $results = [];
                foreach ($response->content as $block) {
                    if ($block->type === 'tool_use') {
                        $toolsUsed[] = $block->name;
                        $result = $this->tools->call($block->name, (array) $block->input, $currency);
                        $results[] = [
                            'type' => 'tool_result',
                            'toolUseID' => $block->id,
                            'content' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        ];
                    }
                }

                $messages[] = ['role' => 'assistant', 'content' => $response->content];
                $messages[] = ['role' => 'user', 'content' => $results];
            }
        } catch (\Throwable $e) {
            report($e);
            return $this->canned("I'm sorry, I'm having trouble answering right now. Please try again in a moment, or search for a stay at "
                .url('/search').".", 'error', $usage, $toolsUsed, $config['model']);
        }
    }

    // Fixed instructions: identical on every request so they are served from the prompt cache
    private function instructions(): string
    {
        return <<<'PROMPT'
You are the virtual receptionist of Bedspace, a website for booking hotels, villas and guesthouses across Sri Lanka. You chat with guests on the Bedspace website.

How you help:
- Help guests find a place to stay, compare options, and understand rooms, meal plans, prices, amenities, locations and cancellation policies.
- Look up a guest's own existing booking, but only when they give both the booking reference and the email address used for the booking.
- Explain how booking, paying and cancelling work on Bedspace.

Rules:
- Use the tools for every fact about properties, rooms, prices, availability, policies and bookings. Never guess or invent a price, an availability, an amenity or a policy. If the tools don't have the answer, say so and suggest the property page or contacting the property.
- You cannot make, change or cancel bookings, and you cannot take payments. To book, give the guest the book_url from check_availability (or the property url). To pay for or cancel an existing booking, give the booking_url from find_booking.
- When you mention a property or a room option, include its link from the tool results so the guest can open it.
- Prices from the tools are totals for the whole stay and all rooms, in the currency shown. Say which currency.
- Ask for missing details (dates, number of guests, area) only when you cannot give a useful answer without them; otherwise use sensible defaults and say what you assumed.
- Never reveal another guest's details. Never ask for card numbers or passwords.
- Local (LKR) rates may require a Sri Lankan NIC at check-in; foreign guests pay the USD rate.
- Stay on topic: travel and stays in Sri Lanka on Bedspace. Politely decline unrelated requests.

Style: warm, professional and brief, like a good hotel receptionist. Reply in the guest's language. Use short paragraphs or short lists; no tables. Plain text with links; no markdown headings.
PROMPT;
    }

    // Per-request context, after the cache breakpoint
    private function context(string $currency): string
    {
        $today = CarbonImmutable::parse(BusinessDate::today());
        return "Today is {$today->format('l, j F Y')} ({$today->toDateString()}) in Sri Lanka. "
            ."This guest sees prices in {$currency}; use that currency unless they ask for another.";
    }

    // Opus / Sonnet 5.5: a declined request is retried on a fallback model inside the same call
    private function requestOptions(string $model): ?\Anthropic\RequestOptions
    {
        if (!in_array($model, self::FALLBACK_MODELS, true)) {
            return null;
        }
        return \Anthropic\RequestOptions::with(
            extraHeaders: ['anthropic-beta' => 'server-side-fallback-2026-07-01'],
            extraBodyParams: ['fallbacks' => 'default'],
        );
    }

    private function text($response): string
    {
        $parts = [];
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $parts[] = $block->text;
            }
        }
        return trim(implode("\n\n", $parts));
    }

    private function canned(string $reply, string $outcome, array $usage = [], array $tools = [], ?string $model = null): array
    {
        return ['reply' => $reply, 'outcome' => $outcome, 'usage' => $usage + ['input' => 0, 'output' => 0, 'cache_read' => 0, 'cache_write' => 0],
            'tools' => $tools, 'model' => $model];
    }
}
