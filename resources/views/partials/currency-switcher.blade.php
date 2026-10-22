{{-- Display-currency switcher for public pages (needs the visitor.currency middleware) --}}
@isset($displayCurrency)
<form method="POST" action="/display-currency" class="d-inline-block">
    @csrf
    <select name="currency" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Currency">
        @foreach(\App\Models\CurrencyType::active()->orderBy('id')->get() as $currency)
            <option value="{{ $currency->code }}" @selected($currency->id === $displayCurrency->id)>{{ $currency->code }}</option>
        @endforeach
    </select>
</form>
@endisset
