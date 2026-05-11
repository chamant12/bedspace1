{{-- resources/views/emails/property_registered.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Property Registration</title>
</head>

<body style="margin:0; padding:0; background-color:#f4f6f8; font-family: Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:20px 0;">
<tr>
<td align="center">

<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.05);">

    <!-- Header -->
    <tr>
        <td style="background:#0d6efd; color:#ffffff; padding:20px; text-align:center;">
            <h2 style="margin:0;">New Property Registered</h2>
        </td>
    </tr>

    <!-- Body -->
    <tr>
        <td style="padding:25px; color:#333;">

            <p style="font-size:15px;">
                Hello,
            </p>

            <p style="font-size:15px;">
                A new property has been successfully registered on your platform.
            </p>

            <!-- Details Table -->
            <table width="100%" cellpadding="8" cellspacing="0" style="margin-top:15px; border-collapse:collapse;">
                <tr style="background:#f8f9fa;">
                    <td><strong>Owner Name</strong></td>
                    <td>{{ $owner_name }}</td>
                </tr>
                <tr>
                    <td><strong>Email</strong></td>
                    <td>{{ $owner_email }}</td>
                </tr>
                <tr style="background:#f8f9fa;">
                    <td><strong>Phone</strong></td>
                    <td>{{ $owner_phone }}</td>
                </tr>
                <tr>
                    <td><strong>Property Name</strong></td>
                    <td>{{ $property_name }}</td>
                </tr>
                <tr style="background:#f8f9fa;">
                    <td><strong>Location</strong></td>
                    <td>{{ $property_location }}</td>
                </tr>
                <tr>
                    <td><strong>Registered At</strong></td>
                    <td>{{ $created_at }}</td>
                </tr>
            </table>

            <!-- CTA -->
            <div style="text-align:center; margin-top:25px;">
                <a href="{{ $admin_url }}"
                   style="background:#0d6efd; color:#ffffff; padding:12px 20px; text-decoration:none; border-radius:5px; display:inline-block;">
                    View in Admin Panel
                </a>
            </div>

        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background:#f8f9fa; padding:15px; text-align:center; font-size:12px; color:#777;">
            This is an automated notification from your system.
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>