<!DOCTYPE html>
<html><head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; color:#222;">
    <h3>New message from the website contact form</h3>
    <p>
        <strong>Name:</strong> {{ $contactMessage->name }}<br/>
        <strong>Email:</strong> {{ $contactMessage->email }}<br/>
        @if($contactMessage->phone)<strong>Phone:</strong> {{ $contactMessage->phone }}<br/>@endif
        @if($contactMessage->subject)<strong>Subject:</strong> {{ $contactMessage->subject }}<br/>@endif
        <strong>Received:</strong> {{ $contactMessage->created_at?->timezone(config('bedspace.timezone'))->format('Y-m-d H:i') }}
    </p>
    <div style="white-space: pre-wrap; border-left: 3px solid #1675ac; padding-left: 12px;">{{ $contactMessage->message }}</div>
    <p style="color:#666; font-size:12px; margin-top:20px;">Reply to this email to answer the visitor directly. All messages are also listed under Website content &gt; Messages in the dashboard.</p>
</body></html>
