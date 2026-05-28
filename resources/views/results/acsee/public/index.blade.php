<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACSEE {{ $yearNumeric }} EXAMINATION RESULTS ENQUIRIES</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            min-height: 100vh;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        
        .header p {
            margin: 5px 0;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .header .government {
            font-weight: bold;
            font-size: 13px;
        }
        
        .header .institution {
            font-weight: bold;
            font-size: 13px;
            margin-top: 10px;
        }
        
        .header .title {
            font-weight: bold;
            font-size: 16px;
            margin-top: 15px;
            text-decoration: underline;
        }
        
        .instruction {
            text-align: center;
            font-weight: bold;
            margin: 20px 0;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .alphabet-nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin: 30px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .alphabet-nav a,
        .alphabet-nav span {
            padding: 8px 12px;
            text-decoration: none;
            color: #003366;
            border: 1px solid #ccc;
            background-color: white;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 35px;
            text-align: center;
        }
        
        .alphabet-nav a:hover {
            background-color: #003366;
            color: white;
            border-color: #003366;
        }
        
        .alphabet-nav .active {
            background-color: #003366;
            color: white;
            border-color: #003366;
        }
        
        .centres-list {
            margin-top: 30px;
            background-color: #fff;
        }
        
        .centre-item {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
        }
        
        .centre-item:hover {
            background-color: #f0f0f0;
        }
        
        .centre-item a {
            color: #003366;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            flex: 1;
        }
        
        .centre-item a:hover {
            text-decoration: underline;
        }
        
        .centre-code {
            font-weight: bold;
            margin-right: 10px;
            color: #666;
        }
        
        .centre-name {
            flex: 1;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 14px;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <p class="government">NATIONAL EXAMINATIONS COUNCIL OF TANZANIA</p>
            <p class="institution">ACSEE {{ $yearNumeric }} EXAMINATION RESULTS ENQUIRIES</p>
        </div>

        <!-- Instruction -->
        <div class="instruction">
            CLICK ANY LETTER BELOW TO FILTER CENTRES BY ALPHABET
        </div>

        <!-- Alphabet Navigation -->
        <div class="alphabet-nav">
            @php
                $letters = array_merge(['ALL'], range('A', 'Z'));
            @endphp
            
            @foreach ($letters as $l)
                @if ($letter === $l)
                    <span class="active">{{ $l }}</span>
                @else
                    <a href="{{ route('results.public.acsee.index', ['year' => $examYear, 'letter' => $l]) }}">{{ $l }}</a>
                @endif
            @endforeach
        </div>

        <!-- Centres List -->
        <div class="centres-list">
            @if ($centres->count() > 0)
                @foreach ($centres as $centre)
                    <div class="centre-item">
                        <a href="{{ route('results.public.acsee.show', ['centreCode' => $centre->code, 'year' => $examYear]) }}">
                            <span class="centre-code">{{ $centre->code }}</span>
                            <span class="centre-name">{{ strtoupper($centre->name) }}</span>
                        </a>
                    </div>
                @endforeach
            @else
                <div class="no-results">
                    No centres found for the selected letter.
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>National Examinations Council of Tanzania</p>
        </div>
    </div>
    <script>
        // Disable Developer Tools for non-admins
        (function() {
            @if(!(auth()->check() && auth()->user()->isAdmin()))
                document.addEventListener('contextmenu', event => event.preventDefault());
                document.onkeydown = function(e) {
                    if (e.keyCode == 123) return false;
                    if (e.ctrlKey && e.shiftKey && (e.keyCode == 'I'.charCodeAt(0) || e.keyCode == 'J'.charCodeAt(0) || e.keyCode == 'C'.charCodeAt(0))) return false;
                    if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
                };
            @endif
        })();
    </script>
</body>
</html>
