@extends('layout.app')
@section('content')
@php($fmt = fn ($v) => number_format($v, 2))
<div class="row">
    @foreach([
        'Gross income (LKR)' => $totals->gross_lkr,
        'Platform commission (LKR)' => $totals->commission_lkr,
        'Net income (LKR)' => $totals->net_to_hotel_lkr,
    ] as $label => $value)
    <div class="col-sm-4 grid-margin stretch-card">
        <div class="card card-rounded"><div class="card-body">
            <p class="statistics-title">{{ $label }}</p>
            <h3 class="rate-percentage">{{ $fmt($value) }}</h3>
        </div></div>
    </div>
    @endforeach
</div>

<div class="col-lg-12 grid-margin stretch-card">
    <div class="card"><div class="card-body">
        <h4 class="card-title">Monthly income</h4>
        @include('reports._monthPicker')
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th class="text-end">Bookings</th>
                        <th class="text-end">Paid online</th>
                        <th class="text-end">Paid at hotel</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Commission</th>
                        <th class="text-end">Net income</th>
                        <th class="text-end">Settlement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->property_name }}</td>
                        <td class="text-end">{{ $row->bookings }}</td>
                        <td class="text-end">{{ $fmt($row->online_lkr) }}</td>
                        <td class="text-end">{{ $fmt($row->hotel_lkr) }}</td>
                        <td class="text-end">{{ $fmt($row->gross_lkr) }}</td>
                        <td class="text-end">{{ $fmt($row->commission_lkr) }}</td>
                        <td class="text-end"><strong>{{ $fmt($row->net_to_hotel_lkr) }}</strong></td>
                        <td class="text-end">
                            @if($row->settlement_lkr >= 0)
                                Platform pays you {{ $fmt($row->settlement_lkr) }}
                            @else
                                <span class="text-danger">You owe {{ $fmt(-$row->settlement_lkr) }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8">No payments in this month.</td></tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="text-end">{{ $totals->bookings }}</th>
                        <th class="text-end">{{ $fmt($totals->online_lkr) }}</th>
                        <th class="text-end">{{ $fmt($totals->hotel_lkr) }}</th>
                        <th class="text-end">{{ $fmt($totals->gross_lkr) }}</th>
                        <th class="text-end">{{ $fmt($totals->commission_lkr) }}</th>
                        <th class="text-end">{{ $fmt($totals->net_to_hotel_lkr) }}</th>
                        <th class="text-end">{{ $fmt($totals->settlement_lkr) }}</th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        <p class="text-muted mt-2 mb-0">Online payments are collected by the platform and paid to you less commission;
            for payments taken at your property you owe the platform the commission.</p>
    </div></div>
</div>
@stop
