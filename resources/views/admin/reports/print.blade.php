<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Cetak Rapor - {{ $grade->student->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #555;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 5px;
            vertical-align: top;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
        }

        .result-box {
            text-align: center;
            padding: 20px;
            border: 2px solid #ddd;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .result-box h2 {
            margin: 0 0 10px;
            font-size: 48px;
        }

        .result-box .status {
            font-size: 20px;
            font-weight: bold;
        }

        .status.passed {
            color: #28a745;
        }

        .status.failed {
            color: #dc3545;
        }

        .violations-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .violations-table th,
        .violations-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .violations-table th {
            background-color: #f8f9fa;
        }

        .signature {
            margin-top: 50px;
            text-align: right;
        }

        .signature-line {
            display: inline-block;
            width: 200px;
            border-bottom: 1px solid #333;
            margin-top: 40px;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }

        .btn-print {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            cursor: pointer;
            border: none;
        }
    </style>
</head>

<body onload="window.print()">

    <div
        class="no-print"
        style="text-align: center;"
    >
        <button
            class="btn-print"
            onclick="window.print()"
        >🖨️ Cetak PDF</button>
    </div>

    <div class="header">
        <h1>LAPORAN HASIL UJIAN SISWA</h1>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">Nama Siswa</td>
            <td>: {{ $grade->student->name }}</td>
            <td class="info-label">Mata Pelajaran</td>
            <td>: {{ $grade->exam->lesson->name }}</td>
        </tr>
        <tr>
            <td class="info-label">Nama Ujian</td>
            <td>: {{ $grade->exam->title }}</td>
            <td class="info-label">Waktu Pengerjaan</td>
            <td>: {{ $grade->start_time ? $grade->start_time->format('d/m/Y H:i') : '-' }}</td>
        </tr>
    </table>

    <div class="result-box">
        <p style="margin: 0; font-size: 16px; color: #666;">
            {{ $grade->exam->isPersonality() ? 'Skor Total' : 'Nilai Akhir' }}
        </p>
        @php
            $threshold = $grade->exam->isPersonality() ? 50 : 70;
            $isPassed = $grade->grade >= $threshold;
        @endphp
        <h2 style="color: {{ $isPassed ? '#28a745' : '#dc3545' }};">
            {{ $grade->grade }}
        </h2>
        @if($grade->exam->isPersonality())
            <div class="status {{ $isPassed ? 'passed' : 'failed' }}">
                {{ $isPassed ? 'BAIK' : 'KURANG BAIK' }}
            </div>
        @else
            <div class="status {{ $isPassed ? 'passed' : 'failed' }}">
                {{ $isPassed ? 'LULUS' : 'TIDAK LULUS' }}
            </div>
        @endif
    </div>

    <div style="margin-bottom: 20px;">
        <strong>Ringkasan {{ $grade->exam->isPersonality() ? 'Hasil' : 'Jawaban' }}:</strong>
        <ul>
            @if($grade->exam->isPersonality())
                <li>Total Poin: {{ $grade->total_points }} / {{ $grade->max_points }}</li>
                <li>Persentase: {{ $grade->grade }}%</li>
            @else
                <li>Total Soal Benar: {{ $grade->total_correct }}</li>
                <li>Total Soal: {{ $grade->exam->questions()->count() }}</li>
            @endif
        </ul>
    </div>

    <div>
        <strong>Catatan Integritas Ujian (Kecurangan):</strong>
        @if ($examGroup && $examGroup->exam_violations && $examGroup->exam_violations->count() > 0)
            <p style="color: #dc3545; margin: 5px 0;">Terdeteksi {{ $examGroup->exam_violations->count() }} aktivitas
                mencurigakan selama ujian.</p>
            <table class="violations-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No.</th>
                        <th>Waktu Kejadian</th>
                        <th>Tipe Pelanggaran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($examGroup->exam_violations as $index => $violation)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ date('d/m/Y H:i:s', strtotime($violation->violation_time)) }}</td>
                            <td>
                                @if ($violation->violation_type === 'tab_switch')
                                    Berpindah Tab / Aplikasi
                                @elseif($violation->violation_type === 'exit_fullscreen')
                                    Keluar Mode Layar Penuh
                                @else
                                    {{ $violation->violation_type }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: #28a745; margin: 5px 0;">
                ✓ Tidak terdeteksi adanya pelanggaran atau kecurangan selama ujian berlangsung. Ujian berjalan lancar
                dan jujur.
            </p>
        @endif
    </div>

    <div class="signature">
        <p>Tanggal Cetak: {{ date('d F Y') }}</p>
        <br>
        <br>
        <div class="signature-line"></div>
        <p style="margin-top: 5px;">Administrator</p>
    </div>

</body>

</html>
