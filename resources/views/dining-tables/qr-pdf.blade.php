<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $diningTable->label }} — table QR</title>
    <style>
        body {
            font-family: sans-serif;
            text-align: center;
            padding-top: 140px;
        }

        h1 {
            font-size: 32px;
            margin-bottom: 48px;
        }

        img {
            width: 320px;
            height: 320px;
        }

        p.helper {
            margin-top: 32px;
            font-size: 14px;
            color: #555555;
        }
    </style>
</head>
<body>
    <h1>{{ $diningTable->label }}</h1>
    <img src="data:image/png;base64,{{ $qrBase64 }}" alt="Table QR code">
    <p class="helper">Scan to view the menu and order</p>
</body>
</html>
