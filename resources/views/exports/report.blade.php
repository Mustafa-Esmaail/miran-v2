<!doctype html>
<html lang="{{ $isRtl ? 'ar' : 'en' }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #22302d;
            font-size: 11px;
            direction: {{ $isRtl ? 'rtl' : 'ltr' }};
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #9a7259;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .title {
            font-size: 17px;
            font-weight: 700;
        }
        .subtitle {
            font-size: 10px;
            color: #6b716f;
        }
        .logo {
            height: 38px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th,
        table.data td {
            border: 1px solid #e2ded8;
            padding: 5px 7px;
            text-align: {{ $isRtl ? 'right' : 'left' }};
        }
        table.data th {
            background: #2b3936;
            color: #ffffff;
            font-weight: 700;
        }
        table.data tr:nth-child(even) td {
            background: #f4f2ef;
        }
        .empty {
            padding: 24px;
            text-align: center;
            color: #6b716f;
        }
    </style>
</head>
<body>
    @php
        $safeText = function ($value): string {
            if ($value === null) {
                return '';
            }

            $clean = @iconv('UTF-8', 'UTF-8//IGNORE', (string) $value);

            return $clean === false ? '' : $clean;
        };
    @endphp

    <table class="header">
        <tr>
            <td>
                <div class="title">{{ $safeText($title) }}</div>
                @if ($period)
                    <div class="subtitle">{{ $safeText($period) }}</div>
                @endif
                <div class="subtitle">{{ __('admin.report.generated_at', ['date' => $generatedAt]) }}</div>
            </td>
            @if ($logoUrl)
                <td style="text-align: {{ $isRtl ? 'left' : 'right' }};">
                    <img class="logo" src="{{ $logoUrl }}" alt="">
                </td>
            @endif
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $safeText($heading) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $safeText($cell) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ max(count($headings), 1) }}">—</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
