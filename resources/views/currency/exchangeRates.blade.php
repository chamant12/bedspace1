@extends('layout.app')
@section('content')
<form action="/update-exchange-rates" method="POST" class="forms-sample">
<div class="col-md-6 grid-margin stretch-card">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Exchange Rates</h4>
            <p class="card-description">
                Update daily. Each booking and payment stores the rate in effect at that time,
                so your income reports can show totals in rupees.
            </p>
            @foreach($currencies as $currency)
            <div class="form-group">
                <label>1 {{ $currency->code }} ({{ $currency->symbol }}) in LKR<span style="color:red;">*</span></label>
                <input step="0.01" min="0.01" type="number" class="form-control" name="rates[{{ $currency->id }}]" required
                       value="{{ old('rates.'.$currency->id, $latest[$currency->id]?->rate) }}">
                <small class="text-muted">
                    @if($latest[$currency->id])
                        Last updated {{ $latest[$currency->id]->rate_date->format('Y-m-d') }}
                        @unless($latest[$currency->id]->rate_date->isToday())
                            <span class="text-danger">(not updated today)</span>
                        @endunless
                    @else
                        <span class="text-danger">Not set yet</span>
                    @endif
                </small>
            </div>
            @endforeach
            @csrf
            <button type="submit" class="btn btn-primary">Update</button>
            <a class="btn btn-light" href="/dashboard">Cancel</a>
        </div>
    </div>
</div>
</form>
@stop
