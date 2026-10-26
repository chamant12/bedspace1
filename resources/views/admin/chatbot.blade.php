@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Chatbot
            @if($enabled)
                <span class="badge badge-success">Live &middot; {{ $model }}</span>
            @else
                <span class="badge badge-secondary">Coming-soon mode</span>
            @endif
        </h4>
        <p class="card-description">
            @unless($enabled)
                The widget is on the website but answers with a "coming soon" message. To go live: install the SDK
                (<code>composer require anthropic-ai/sdk guzzlehttp/guzzle</code>), set <code>ANTHROPIC_API_KEY</code> and
                <code>CHATBOT_ENABLED=true</code>.
            @endunless
            Daily limits: {{ config('bedspace.chatbot.max_messages_per_visitor_per_day') }} messages per visitor,
            {{ config('bedspace.chatbot.max_messages_per_day') }} for the whole site.
        </p>

        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-3"><label>Month</label><input type="month" name="month" class="form-control" value="{{ $month }}"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-success">Show</button></div>
        </form>

        <table class="table table-striped">
            <thead><tr><th>Model</th><th class="text-end">Messages</th><th class="text-end">Answered</th><th class="text-end">Visitors</th>
                <th class="text-end">Input tokens</th><th class="text-end">Cached</th><th class="text-end">Output tokens</th>
                <th class="text-end">Avg reply time</th><th class="text-end">Est. cost (USD)</th></tr></thead>
            <tbody>
            @forelse($byModel as $row)
                <tr>
                    <td>{{ $row->model ?? 'Not sent to the model' }}</td>
                    <td class="text-end">{{ number_format($row->messages) }}</td>
                    <td class="text-end">{{ number_format($row->answered) }}</td>
                    <td class="text-end">{{ number_format($row->visitors) }}</td>
                    <td class="text-end">{{ number_format($row->input_tokens) }}</td>
                    <td class="text-end">{{ number_format($row->cache_read_tokens) }}</td>
                    <td class="text-end">{{ number_format($row->output_tokens) }}</td>
                    <td class="text-end">{{ number_format($row->avg_ms / 1000, 1) }} s</td>
                    <td class="text-end">{{ $row->cost_usd !== null ? number_format($row->cost_usd, 2) : '–' }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No chatbot messages in this month.</td></tr>
            @endforelse
            </tbody>
        </table>
        <p class="text-muted small">Cost is an estimate from the prices in config/bedspace.php; the Claude Console shows the exact bill.</p>
    </div></div>
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Recent conversations</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>When</th><th>Question</th><th>Answer</th><th>Tools</th><th>Outcome</th></tr></thead>
                <tbody>
                @foreach($recent as $log)
                    <tr>
                        <td style="white-space:nowrap;">{{ $log->created_at?->timezone(config('bedspace.timezone'))->format('m-d H:i') }}</td>
                        <td style="white-space:normal; max-width:280px;">{{ \Illuminate\Support\Str::limit($log->question, 200) }}</td>
                        <td style="white-space:normal; max-width:420px;">{{ \Illuminate\Support\Str::limit($log->answer, 300) }}</td>
                        <td>{{ $log->tools_used }}</td>
                        <td>{{ $log->outcome }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div></div>
</div>
@stop
