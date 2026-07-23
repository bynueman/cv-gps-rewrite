<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #1c1712; line-height: 1.6;">
    <h2 style="margin-bottom: 4px;">Pesan baru dari form kontak gpsfood.id</h2>
    <p style="color: #6b5f52; margin-top: 0;">Kategori: {{ $contactMessage->topic }}</p>

    <table cellpadding="6" cellspacing="0" style="margin: 16px 0; border-collapse: collapse;">
        <tr>
            <td style="font-weight: bold; vertical-align: top;">Nama</td>
            <td>{{ $contactMessage->name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; vertical-align: top;">Email</td>
            <td>{{ $contactMessage->email }}</td>
        </tr>
    </table>

    <p style="font-weight: bold; margin-bottom: 4px;">Pesan:</p>
    <p style="white-space: pre-wrap;">{{ $contactMessage->message }}</p>

    <hr style="margin: 24px 0; border: none; border-top: 1px solid #e5ddd2;">
    <p style="color: #9a8f80; font-size: 12px;">
        Dikirim otomatis dari form kontak di gpsfood.id. Balas email ini langsung untuk merespons {{ $contactMessage->name }}.
    </p>
</body>
</html>
