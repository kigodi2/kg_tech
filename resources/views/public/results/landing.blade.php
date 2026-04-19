<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Results {{ strtoupper($examType) }} {{ $examYear }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; margin: 0; }
        .wrap { max-width: 760px; margin: 80px auto; background: #fff; border: 1px solid #d9e2ef; border-radius: 10px; padding: 24px; }
        h1 { margin: 0 0 8px; font-size: 24px; color: #0b3a75; }
        p { margin: 0 0 16px; color: #394b65; }
        .row { display: flex; gap: 8px; }
        input { flex: 1; border: 1px solid #b7c6dd; border-radius: 6px; padding: 10px 12px; font-size: 14px; }
        button { border: 0; border-radius: 6px; background: #0b3a75; color: #fff; padding: 10px 16px; font-weight: 700; cursor: pointer; }
        .hint { margin-top: 10px; font-size: 13px; color: #5b6f8d; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>PUBLIC RESULTS {{ strtoupper($examType) }} {{ $examYear }}</h1>
    <p>This portal is accessed using a secure school link token or the full school link.</p>
    <form class="row" method="GET" action="{{ url('/results/' . $examYear . '/' . strtolower($examType)) }}">
        <input type="text" name="token" placeholder="Paste token or full school link here">
        <button type="submit">Open</button>
    </form>
    <div class="hint">Example: paste either the token itself or `https://portal.irms.ac.tz/r/&lt;token&gt;`.</div>
</div>
</body>
</html>
