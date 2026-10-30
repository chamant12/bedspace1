<!DOCTYPE html>
<html><head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; color:#222;">
    <p>Dear {{ $owner->name }},</p>
    <p>Your EpickTours property owner account has been approved. You can now add your properties, room types, rates and staff accounts.</p>
    <p><a href="{{ url('/login') }}">Sign in to EpickTours</a></p>
</body></html>
