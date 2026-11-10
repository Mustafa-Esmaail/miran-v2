<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enrollments Export</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111;
            font-size: 12px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #eee;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .title {
            font-size: 18px;
            font-weight: 700;
        }
        .subtitle {
            font-size: 11px;
            color: #666;
        }
        .logo {
            height: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background: #f5f5f5;
        }
        .muted {
            color: #666;
        }
</style>
</head>
<body>
    @php
        $safeText = function ($value): string {
            if ($value === null) {
                return '';
            }

            $text = (string) $value;
            $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

            return $clean === false ? '' : $clean;
        };
    @endphp
    <div class="header">
        <div>
            <div class="title">Enrollments Export</div>
            <div class="subtitle">Generated {{ $generatedAt }}</div>
        </div>
        @if ($logoUrl)
            <img class="logo" src="{{ $logoUrl }}" alt="Logo">
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Course</th>
                <th>Course Group</th>
                <th>Status</th>
                <th>Invited</th>
                <th>Activated At</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $enrollment)
                <tr>
                    <td>{{ $safeText($enrollment->user?->name) }}</td>
                    <td>{{ $safeText($enrollment->user?->email) }}</td>
                    <td>{{ $safeText($enrollment->course?->title) }}</td>
                    <td>{{ $safeText($enrollment->courseGroup?->title) }}</td>
                    <td>{{ $safeText($enrollment->status?->value ?? $enrollment->status) }}</td>
                    <td>{{ $enrollment->is_invited ? __('admin.labels.yes') : __('admin.labels.no') }}</td>
                    <td>{{ $enrollment->activated_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $enrollment->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="muted">No records</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
