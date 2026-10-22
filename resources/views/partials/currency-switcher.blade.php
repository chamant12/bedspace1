{{-- Currency switcher for public pages (needs the visitor.currency middleware) --}}
@isset($visitorCurrency)
<form method="POST" action="/currency" class="d-inline-block">
    @csrf
    <select name="currency" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Currency">
        @foreach(\App\Models\CurrencyType::active()->orderBy('id')->get() as $currency)
            <option value="{{ $currency->code }}" @selected($currency->id === $visitorCurrency->id)>{{ $currency->code }}</option>
        @endforeach
    </select>
</form>
@endisset
