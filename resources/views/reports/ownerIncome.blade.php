@extends('layout.app')
@section('content')
@php($fmt = fn ($v) => number_format($v, 2))
@foreach($totals as $currency => $total)
<div class="row">
    @foreach([
        "Gross income ($currency)" => $total->gross,
        "Platform commission ($currency)" => $total->commission,
        "Net income ($currency)" => $total->net_to_hotel,
    ] as $label => $value)
    <div class="col-sm-4 grid-margin stretch-card">
        <div class="card card-rounded"><div class="card-body">
            <p class="statistics-title">{{ $label }}</p>
            <h3 class="rate-percentage">{{ $fmt($value) }}</h3>
        </div></div>
    </div>
    @endforeach
</div>
@endforeach

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Monthly income</h4>
        <p class="card-description">LKR payments are totalled in LKR and USD payments in USD. The two currencies are never converted or combined.</p>
        @include('reports._monthPicker')
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Currency</th>
                        <th class="text-end">Bookings</th>
                        <th class="text-end">Paid online</th>
                        <th class="text-end">Paid at hotel</th>
                        <th class="text-end">Refunds</th>
                        <th class="text-end">Gross (net)</th>
                        <th class="text-end">Commission</th>
                        <th class="text-end">Net income</th>
                        <th class="text-end">Settlement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->property_name }}</td>
                        <td>{{ $row->currency }}</td>
                        <td class="text-end">{{ $row->bookings }}</td>
                        <td class="text-end">{{ $fmt($row->online) }}</td>
                        <td class="text-end">{{ $fmt($row->hotel) }}</td>
                        <td class="text-end">{{ $row->refunds > 0 ? "− ".$fmt($row->refunds) : "–" }}</td>
                        <td class="text-end">{{ $fmt($row->gross) }}</td>
                        <td class="text-end">{{ $fmt($row->commission) }}</td>
                        <td class="text-end"><strong>{{ $fmt($row->net_to_hotel) }}</strong></td>
                        <td class="text-end">
                            @if($row->settlement >= 0)
                                Platform pays you {{ $row->currency }} {{ $fmt($row->settlement) }}
                            @else
                                <span class="text-danger">You owe {{ $row->currency }} {{ $fmt(-$row->settlement) }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10">No payments in this month.</td></tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                <tfoot>
                    @foreach($totals as $currency => $total)
                    <tr>
                        <th>Total {{ $currency }}</th>
                        <th>{{ $currency }}</th>
                        <th class="text-end">{{ $total->bookings }}</th>
                        <th class="text-end">{{ $fmt($total->online) }}</th>
                        <th class="text-end">{{ $fmt($total->hotel) }}</th>
                        <th class="text-end">{{ $total->refunds > 0 ? "− ".$fmt($total->refunds) : "–" }}</th>
                        <th class="text-end">{{ $fmt($total->gross) }}</th>
                        <th class="text-end">{{ $fmt($total->commission) }}</th>
                        <th class="text-end">{{ $fmt($total->net_to_hotel) }}</th>
                        <th class="text-end">{{ $fmt($total->settlement) }}</th>
                    </tr>
                    @endforeach
                </tfoot>
                @endif
            </table>
        </div>
        <p class="text-muted mt-2 mb-0">Online payments are collected by the platform and paid to you less commission;
            for payments taken at your property you owe the platform the commission.</p>
    </div></div>
</div>
@stop
