@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Contact messages</h4>
            <a href="/admin/cms" class="btn btn-light btn-sm">Back</a>
        </div>
        <div class="mb-3">
            @foreach(['' => 'All', 'new' => 'New', 'read' => 'Read', 'replied' => 'Replied'] as $value => $label)
                <a href="/admin/cms/messages{{ $value ? '?status='.$value : '' }}" class="btn btn-sm {{ request('status', '') === $value ? 'btn-primary text-white' : 'btn-outline-primary' }}">{{ $label }}</a>
            @endforeach
        </div>
        <table class="table table-striped">
            <thead><tr><th>Received</th><th>From</th><th>Subject</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($messages as $message)
                <tr class="{{ $message->status === 'new' ? 'fw-bold' : '' }}">
                    <td>{{ $message->created_at?->timezone(config('bedspace.timezone'))->format('Y-m-d H:i') }}</td>
                    <td>{{ $message->name }}<br/><small class="text-muted">{{ $message->email }}</small></td>
                    <td style="white-space:normal;">{{ $message->subject }}<br/><small class="text-muted">{{ \Illuminate\Support\Str::limit($message->message, 90) }}</small></td>
                    <td><span class="badge {{ ['new' => 'badge-warning', 'read' => 'badge-secondary', 'replied' => 'badge-success'][$message->status] ?? 'badge-secondary' }}">{{ ucfirst($message->status) }}</span></td>
                    <td><a href="/admin/cms/messages/{{ $message->id }}" class="btn btn-sm btn-primary text-white">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No messages.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $messages->links() }}</div>
    </div></div>
</div>
@stop
