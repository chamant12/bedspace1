@extends('layout.app')
@section('content')
<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Audit log</h4>
        <p class="card-description">Every change to properties, rates, inventory, bookings, payments, refunds, staff and settlements, with who made it and when.</p>

        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-3">
                <label>Search</label>
                <input type="text" name="q" class="form-control" placeholder="Description, record or user" value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <label>Property</label>
                <select name="property_id" class="form-control">
                    <option value="">All</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" @selected(request('property_id') == $property->id)>{{ $property->property_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>Action</label>
                <select name="action" class="form-control">
                    <option value="">All</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ rtrim($action, '.') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>From</label>
                <input type="date" name="from" class="form-control" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label>To</label>
                <input type="date" name="to" class="form-control" value="{{ request('to') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-success">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Details</th><th>IP</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="white-space:nowrap;">{{ $log->created_at?->timezone(config('bedspace.timezone'))->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? 'Guest / system' }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->subject_type }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}</td>
                        <td style="white-space:normal; max-width:520px;">
                            @if($log->description)<div>{{ $log->description }}</div>@endif
                            @if($log->changes)
                                <small class="text-muted">
                                    @foreach(collect($log->changes)->take(12) as $field => $change)
                                        <span class="d-inline-block me-2">{{ $field }}: {{ is_scalar($change['from'] ?? null) ? \Illuminate\Support\Str::limit((string) $change['from'], 40) : '–' }} &rarr; {{ is_scalar($change['to'] ?? null) ? \Illuminate\Support\Str::limit((string) $change['to'], 40) : json_encode($change['to'] ?? null) }}</span>
                                    @endforeach
                                </small>
                            @endif
                        </td>
                        <td>{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No entries.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $logs->links() }}</div>
    </div></div>
</div>
@stop
