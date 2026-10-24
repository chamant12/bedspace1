<!DOCTYPE html>
<html><head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; color:#222;">
    <h3>New property owner awaiting approval</h3>
    <p>
        Name: {{ $owner->name }}<br/>
        Email: {{ $owner->email }}<br/>
        Mobile: {{ $owner->phone }}<br/>
        Signed up: {{ $owner->created_at?->timezone(config('bedspace.timezone'))->format('Y-m-d H:i') }}
    </p>
    <p><a href="{{ url('/admin/owners') }}">Review in the admin area</a></p>
</body></html>
