<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Project</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #2d3748; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #cbd5e0; padding: 10px; text-align: left; }
        th { background-color: #edf2f7; }
        .header { text-align: center; margin-bottom: 30px; }
        .footer { margin-top: 30px; text-align: right; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PROJECT</h1>
        <p>{{ $project->nama_project }} — {{ $periode }}</p>
        <p>Dicetak pada: {{ $date }}</p>
    </div>

    @if($project->tasks->isEmpty())
        <p>Tidak ada tugas dalam periode ini.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul Tugas</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Assignee</th>
                </tr>
            </thead>
            <tbody>
                @foreach($project->tasks as $index => $task)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $task->judul }}</td>
                        <td>{{ \Carbon\Carbon::parse($task->due_date)->format('d-m-Y') }}</td>
                        <td>
                            @if($task->board && $task->board->nama_board === 'Done')
                                Selesai
                            @else
                                {{ $task->board ? $task->board->nama_board : 'Tidak Diketahui' }}
                            @endif
                        </td>
                        <td>
                            @if($task->assignees->isNotEmpty())
                                {{ $task->assignees->pluck('name')->join(', ') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Sistem HRIS Damirich Group
    </div>
</body>
</html>