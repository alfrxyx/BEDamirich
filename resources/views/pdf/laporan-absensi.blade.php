<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 20px;
            color: #1e40af;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .header .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .header .company-info {
            font-size: 10px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            width: 150px;
            padding: 4px 0;
            font-weight: bold;
            color: #475569;
        }
        
        .info-value {
            display: table-cell;
            padding: 4px 0;
            color: #1e293b;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        thead {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
        }
        
        th {
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-tepat-waktu {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-terlambat {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-disetujui {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-ditolak {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-menunggu {
            background: #fef3c7;
            color: #92400e;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }
        
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 30px;
        }
        
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 10px;
        }
        
        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #333;
            padding-top: 5px;
            display: inline-block;
            min-width: 200px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #64748b;
            font-size: 9px;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <div style="text-align: center; margin-bottom: 15px;">
<!-- <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo_damirich.png'))) }}"
     alt="Logo"
     style="height: 60px; width: auto;"> -->
    </div>
        <div class="company-name">PT DAMRICH INDONESIA</div>
        <div class="company-info">
            Jl. Contoh Alamat No. 123, Jakarta Selatan 12345<br>
            Telp: (021) 1234-5678 | Email: info@damrich.id
        </div>
        <h1>{{ $title }}</h1>
    </div>

    <!-- INFO SECTION -->
    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Periode</div>
            <div class="info-value">: {{ $periode }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Tanggal Cetak</div>
            <div class="info-value">: {{ $tanggal_cetak }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Dicetak Oleh</div>
            <div class="info-value">: {{ $dicetak_oleh }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Total Data</div>
            <div class="info-value">: {{ count($data) }} record</div>
        </div>
    </div>

    <!-- TABLE CONTENT -->
    @if($type === 'absensi')
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">No</th>
                    <th style="width: 12%;">Tanggal</th>
                    <th style="width: 20%;">Nama Karyawan</th>
                    <th style="width: 15%;">Divisi</th>
                    <th style="width: 10%;">Jam Masuk</th>
                    <th style="width: 10%;">Jam Pulang</th>
                    <th style="width: 12%;">Durasi</th>
                    <th style="width: 13%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['tanggal'] }}</td>
                        <td><strong>{{ $item['nama'] }}</strong></td>
                        <td>{{ $item['divisi'] }}</td>
                        <td class="text-center">{{ $item['jam_masuk'] }}</td>
                        <td class="text-center">{{ $item['jam_pulang'] }}</td>
                        <td class="text-center">{{ $item['durasi'] }}</td>
                        <td class="text-center">
                            <span class="status-badge {{ strtolower($item['status']) === 'terlambat' ? 'status-terlambat' : 'status-tepat-waktu' }}">
                                {{ $item['status'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Tidak ada data absensi pada periode ini</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">No</th>
                    <th style="width: 18%;">Periode Cuti</th>
                    <th style="width: 20%;">Nama Karyawan</th>
                    <th style="width: 15%;">Divisi</th>
                    <th style="width: 12%;">Jenis</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 12%;">Alasan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['tanggal'] }}</td>
                        <td><strong>{{ $item['nama'] }}</strong></td>
                        <td>{{ $item['divisi'] }}</td>
                        <td>{{ $item['jenis'] }}</td>
                        <td class="text-center">
                            <span class="status-badge 
                                @if($item['status'] === 'Disetujui') status-disetujui
                                @elseif($item['status'] === 'Ditolak') status-ditolak
                                @else status-menunggu
                                @endif">
                                {{ $item['status'] }}
                            </span>
                        </td>
                        <td>{{ Str::limit($item['alasan'], 50) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Tidak ada data cuti pada periode ini</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- FOOTER & SIGNATURE -->
    <div class="footer">
        <div class="signature-section">
            <div class="signature-box">
                <div>Mengetahui,</div>
                <div><strong>HRD Manager</strong></div>
                <div class="signature-line">
                    ( _________________________ )
                </div>
            </div>
            <div class="signature-box">
                <div>Jakarta, {{ Carbon\Carbon::now()->format('d F Y') }}</div>
                <div><strong>Admin</strong></div>
                <div class="signature-line">
                    ( {{ $dicetak_oleh }} )
                </div>
            </div>
        </div>
        
        <div class="text-center text-muted" style="margin-top: 30px;">
            <em>Dokumen ini digenerate otomatis oleh sistem Damrich ERP</em>
        </div>
    </div>
</body>
</html>