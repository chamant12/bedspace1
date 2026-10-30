@extends('layout.app')
@section('content')
<div class="col-lg-9 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">{{ $message->subject ?: 'Message' }}</h4>
            <a href="/admin/cms/messages" class="btn btn-light btn-sm">Back</a>
        </div>
        <p class="mb-1"><strong>{{ $message->name }}</strong> &middot; <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: 'your message')) }}">{{ $message->email }}</a>
            @if($message->phone) &middot; {{ $message->phone }} @endif</p>
        <p class="text-muted small">Received {{ $message->created_at?->timezone(config('bedspace.timezone'))->format('Y-m-d H:i') }} &middot; IP {{ $message->ip_address }}</p>
        <div class="border rounded p-3 mb-3" style="white-space: pre-wrap;">{{ $message->message }}</div>
        <form method="POST" action="/admin/cms/messages/{{ $message->id }}/status" class="d-flex gap-2 align-items-center">
            @csrf
            <select name="status" class="form-control form-control-sm" style="width:auto;">
                @foreach(['new' => 'New', 'read' => 'Read', 'replied' => 'Replied'] as $value => $label)
                    <option value="{{ $value }}" @selected($message->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary text-white">Update status</button>
            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: 'your message')) }}" class="btn btn-sm btn-outline-primary">Reply by email</a>
        </form>
    </div></div>
</div>
@stop
