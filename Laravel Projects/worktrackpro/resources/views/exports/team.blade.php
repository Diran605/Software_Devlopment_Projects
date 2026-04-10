@php
    $orgColor = optional($organisation ?? null)->primary_color ?? '#0d9488';
@endphp
<!DOCTYPE html>
<html>
<head>
    <title>Worker Productivity Report</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header img { max-height: 80px; }
        .header h1 { color: {{ $orgColor }}; margin: 10px 0 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { border-bottom: 1px solid #ddd; padding: 10px 8px; text-align: left; }
        th { background-color: {{ $orgColor }}20; color: {{ $orgColor }}; }
        .pct { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($organisation) && $organisation->letterhead)
            <img src="{{ storage_path('app/public/' . $organisation->letterhead) }}" alt="Letterhead" style="width: 100%; max-height: 150px; object-fit: cover;">
        @elseif(isset($organisation) && $organisation->logo)
            <img src="{{ storage_path('app/public/' . $organisation->logo) }}" alt="Logo">
        @endif
        <h1>{{ optional($organisation ?? null)->name ?? 'Organisation' }} Productivity Report</h1>
        <p>Generated on: {{ date('F j, Y') }} | Period: This Week</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Worker</th>
                <th>Department</th>
                <th>Total Hours</th>
                <th>Direct Work %</th>
                <th>Execution Rate</th>
                <th>Total Planned</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report as $worker)
            <tr>
                <td><strong>{{ $worker['user'] }}</strong></td>
                <td>{{ $worker['department'] }}</td>
                <td>{{ $worker['total_hours'] }} hrs</td>
                <td class="pct">{{ $worker['direct_pct'] }}%</td>
                <td>{{ $worker['execution_rate'] }}%</td>
                <td>{{ $worker['total_planned'] }} task(s)</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
