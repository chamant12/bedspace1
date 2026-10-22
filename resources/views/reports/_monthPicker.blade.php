<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label>Month</label>
        <input type="month" name="month" class="form-control" value="{{ $month }}" required>
    </div>
    <div class="col-md-5 d-flex gap-2">
        <button type="submit" class="btn btn-success">Show</button>
        <a href="?month={{ $month }}&format=csv" class="btn btn-outline-primary">Download CSV</a>
    </div>
</form>
<p class="text-muted">Completed payments received in {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }} (Sri Lanka time),
    converted to LKR at the exchange rate recorded with each payment.</p>
