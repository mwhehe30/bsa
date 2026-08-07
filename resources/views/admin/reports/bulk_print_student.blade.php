<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Cetak Rapor Massal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }

        .report-page {
            page-break-after: always;
            margin-bottom: 50px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #ccc;
        }

        .report-page:last-child {
            page-break-after: auto;
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
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

        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        .grades-table th,
        .grades-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .grades-table th {
            background-color: #f8f9fa;
        }

        .text-left {
            text-align: left !important;
        }

        .status-passed {
            color: #28a745;
            font-weight: bold;
        }

        .status-failed {
            color: #dc3545;
            font-weight: bold;
        }

        .fraud-alert {
            color: #dc3545;
            font-size: 12px;
            font-weight: bold;
        }

        .fraud-ok {
            color: #28a745;
            font-size: 12px;
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

            .report-page {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
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

<body>

    <div
        class="no-print"
        style="text-align: center;"
    >
        <button
            class="btn-print"
            onclick="window.print()"
        >🖨️ Cetak PDF</button>
    </div>

    @foreach ($studentsData as $data)
        @php
            $student = $data['student'];
            $grades = $data['grades'];
        @endphp

        <div class="report-page">
            <div class="header">
                <h1>REKAPITULASI HASIL UJIAN SISWA</h1>
            </div>

            <table class="info-table">
                <tr>
                    <td class="info-label">Nama Lengkap</td>
                    <td>: {{ $student->name }}</td>
                    <td class="info-label">Total Ujian Diikuti</td>
                    <td>: {{ $grades->count() }} Kali</td>
                </tr>
            </table>

            <table class="grades-table">
                <thead>
                    <tr>
                        <th style="width: 5%">No.</th>
                        <th class="text-left">Nama Ujian</th>
                        <th class="text-left">Mata Pelajaran</th>
                        <th>Tanggal</th>
                        <th>Benar</th>
                        <th>Nilai</th>
                        <th>Status</th>
                        <th>Catatan Integritas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grades as $index => $grade)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-left">{{ $grade->exam->title }}</td>
                            <td class="text-left">{{ $grade->exam->lesson->name }}</td>
                            <td>{{ $grade->created_at->format('d/m/Y') }}</td>
                            <td>
                                @if($grade->exam->isPersonality())
                                    {{ $grade->total_points }}/{{ $grade->max_points }}
                                @else
                                    {{ $grade->total_correct }}
                                @endif
                            </td>
                            <td style="font-weight: bold; font-size: 16px;">{{ $grade->grade }}</td>
                            <td>
                                @php
                                    $threshold = $grade->exam->isPersonality() ? 50 : 70;
                                    $isPassed = $grade->grade >= $threshold;
                                @endphp
                                @if($grade->exam->isPersonality())
                                    @if ($isPassed)
                                        <span class="status-passed">Baik</span>
                                    @else
                                        <span class="status-failed">Kurang Baik</span>
                                    @endif
                                @else
                                    @if ($isPassed)
                                        <span class="status-passed">Lulus</span>
                                    @else
                                        <span class="status-failed">Tidak Lulus</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if ($grade->exam_group && $grade->exam_group->exam_violations && $grade->exam_group->exam_violations->count() > 0)
                                    <span class="fraud-alert">Terdeteksi
                                        {{ $grade->exam_group->exam_violations->count() }} Kecurangan</span>
                                @else
                                    <span class="fraud-ok">Aman / Jujur</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">Belum ada riwayat ujian yang diikuti oleh siswa ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="signature">
                <p>Tanggal Cetak: {{ date('d F Y') }}</p>
                <br><br>
                <div class="signature-line"></div>
                <p style="margin-top: 5px;">Administrator</p>
            </div>
        </div>
    @endforeach

</body>
<script>
    window.onload = function() {
        window.print();
    }
</script>

</html>
