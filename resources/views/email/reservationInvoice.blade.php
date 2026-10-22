@php($r = $reservation)
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Booking {{ $r->reference }}</title></head>
<body style="font-family: Arial, sans-serif; color:#222; max-width:640px; margin:0 auto;">
    <h2 style="margin-bottom:4px;">{{ $isCopy ? 'New booking received' : 'Booking confirmation & invoice' }}</h2>
    <p style="margin-top:0;color:#666;">Reference <strong>{{ $r->reference }}</strong> &middot; {{ $r->status->label() }}</p>

    @unless($isCopy)
        <p>Dear {{ $r->guest_name }}, thank you for your booking. Please keep this email for your records.</p>
    @endunless

    <table cellpadding="6" style="border-collapse:collapse; width:100%;">
        <tr><td style="color:#666;">Property</td><td><strong>{{ $r->property->property_name }}</strong><br/>{{ $r->property->address }}</td></tr>
        <tr><td style="color:#666;">Check-in</td><td>{{ $r->check_in->format('D, d M Y') }}</td></tr>
        <tr><td style="color:#666;">Check-out</td><td>{{ $r->check_out->format('D, d M Y') }}</td></tr>
        <tr><td style="color:#666;">Nights</td><td>{{ $r->nights }}</td></tr>
        <tr><td style="color:#666;">Guest</td><td>{{ $r->guest_name }}<br/>{{ $r->guest_email }} &middot; {{ $r->guest_phone }}</td></tr>
        @if($r->special_requests)
            <tr><td style="color:#666;">Special requests</td><td>{{ $r->special_requests }}</td></tr>
        @endif
        <tr><td style="color:#666;">Payment</td><td>{{ $r->payment_method->label() }}</td></tr>
    </table>

    <h3>Invoice</h3>
    <table cellpadding="6" style="border-collapse:collapse; width:100%; border:1px solid #ddd;">
        <tr style="background:#f5f5f5;">
            <th align="left">Room</th><th align="right">Rooms</th><th align="right">Amount</th>
        </tr>
        @foreach($r->rooms as $line)
            <tr style="border-top:1px solid #ddd;">
                <td>{{ $line->roomType->roomType }} &ndash; {{ $line->occupancy->occupancy_name }}, {{ $line->mealType->mealType }}<br/>
                    <small style="color:#666;">
                        @foreach($line->nightly_rates as $date => $price)
                            {{ $date }}: {{ $r->money($price) }}@unless($loop->last), @endunless
                        @endforeach
                        (per room)
                    </small>
                </td>
                <td align="right">{{ $line->rooms }}</td>
                <td align="right">{{ $r->money($line->subtotal) }}</td>
            </tr>
            @foreach($line->discounts as $discount)
                <tr><td colspan="2">&nbsp;&nbsp;Discount: {{ $discount->description }}</td><td align="right">&minus; {{ $r->money($discount->amount) }}</td></tr>
            @endforeach
        @endforeach
        <tr style="border-top:2px solid #222;">
            <td colspan="2"><strong>Total</strong></td><td align="right"><strong>{{ $r->money($r->total) }}</strong></td>
        </tr>
    </table>

    @if($r->payment_method === \App\Enums\PaymentMethod::PayAtHotel)
        <p>The total is payable at the property.</p>
    @endif

    <p style="color:#666; font-size:12px; margin-top:24px;">
        Questions about this booking? Contact {{ $r->property->property_name }}
        @if($r->property->primaryContact) on {{ $r->property->primaryContact->mobile }} @endif
        and quote reference {{ $r->reference }}.
    </p>
</body>
</html>
