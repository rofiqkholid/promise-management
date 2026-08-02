<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Quotation Tooling</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 11px; background-color: #ffffff; padding: 20px;">

    <table style="border-collapse: collapse; width: 100%; background-color: #ffffff; margin-bottom: 20px; border: 1.5px solid #000000;">
        <thead>
            <!-- ================= INFO HEADER (ROW 1 - 5) ================= -->
            <tr>
                <!-- Logo Summit (Kolom 1 - 8) -->
                <td colspan="8" rowspan="5" style="width: 20%; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; font-size: 12px;">
                    SUMMIT ADYAWINSA INDONESIA
                </td>
                <!-- Supplier Logo (Kolom 9) -->
                <td colspan="1" rowspan="5" style="width: 12%; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; font-size: 11px;">
                    SUPPLIER LOGO
                </td>
                <!-- Quotation Title (Kolom 10 - 23, Row 1-2) -->
                <td colspan="14" rowspan="2" style="border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; font-size: 14px;">
                    QUOTATION TOOLING
                </td>
                <!-- Row 1 & 2 Exchange Rate Column (Kolom 24 - 25) -> Kosong -->
                <td colspan="2" style="border: 1.5px solid #000000; text-align: center; vertical-align: middle;"></td>
            </tr>
            <tr>
                <!-- Row 2 Col 24-25 empty -->
                <td colspan="2" style="border: 1.5px solid #000000; text-align: center; vertical-align: middle;"></td>
            </tr>
            <tr>
                <!-- Supplier Name (Kolom 10 - 23, Row 3-5) -->
                <td colspan="14" rowspan="3" style="border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; font-size: 16px;">
                    Supplier Name
                </td>
                <!-- Exchange Rate Dimulai dari Baris 3 (Kuning) -->
                <td colspan="2" style="background-color: #ffff00; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; font-size: 11px;">
                    Exchange Rate
                </td>
            </tr>
            <tr>
                <!-- Row 4 Exchange Rate Currency (Kuning) -->
                <td colspan="2" style="background-color: #ffff00; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; font-size: 11px;">
                    {{ $currency ?? '' }}
                </td>
            </tr>
            <tr>
                <!-- Row 5 Exchange Rate Value (Kuning) -->
                <td colspan="2" style="background-color: #ffff00; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; font-size: 11px;">
                    {{ !empty($exchangeRate) ? (float)$exchangeRate : '' }}
                </td>
            </tr>

            <!-- ================= TABLE HEADER (ROW 6 - 9) ================= -->
            <!-- Row 6 -->
            <tr>
                <th rowspan="4" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 35px; white-space: normal; word-wrap: break-word;">No</th> <!-- 1 -->
                <th rowspan="4" colspan="6" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 100px; white-space: normal; word-wrap: break-word;">NEW / COMMON / MODIFY</th> <!-- 2-7 -->
                <th rowspan="4" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 150px; min-width: 150px; white-space: normal; word-wrap: break-word;">Part No.</th> <!-- 8 -->
                <th rowspan="4" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 220px; min-width: 220px; white-space: normal; word-wrap: break-word;">Part Name</th> <!-- 9 -->
                <th rowspan="4" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 130px; min-width: 130px; white-space: normal; word-wrap: break-word;">Material Spec</th> <!-- 10 -->
                <th rowspan="4" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 80px; min-width: 80px; white-space: normal; word-wrap: break-word;">Thickness</th> <!-- 11 -->
                <th rowspan="4" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 130px; white-space: normal; word-wrap: break-word;">Process Name</th> <!-- 12 -->
                <th rowspan="4" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 80px; white-space: normal; word-wrap: break-word;">Process</th> <!-- 13 -->
                <th colspan="12" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; white-space: normal; word-wrap: break-word;">TOOLING</th> <!-- 14-25 -->
            </tr>
            <!-- Row 7 -->
            <tr>
                <th colspan="10" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; white-space: normal; word-wrap: break-word;">DIES-JIG-CF</th>
                <th colspan="2" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; white-space: normal; word-wrap: break-word;">Tooling Cost</th>
            </tr>
            <!-- Row 8 (Tinggi baris dinaikkan untuk rotated headers) -->
            <tr style="height: 60px;">
                <th rowspan="2" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 110px; white-space: normal; word-wrap: break-word;">NEW DIES / MODIF / COMMON</th>
                <th rowspan="2" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 45px; white-space: normal; word-wrap: break-word;">OP</th>
                <th rowspan="2" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 200px; min-width: 200px; white-space: normal; word-wrap: break-word;">Process Name</th>
                <th colspan="4" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; white-space: normal; word-wrap: break-word;">Jumlah Tooling</th>
                <th rowspan="2" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; writing-mode: vertical-rl; transform: rotate(-90deg); -webkit-transform: rotate(-90deg); text-rotation: 90; width: 45px; height: 60px;">Tonage (T)</th>
                <th rowspan="2" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; writing-mode: vertical-rl; transform: rotate(-90deg); -webkit-transform: rotate(-90deg); text-rotation: 90; width: 45px; height: 60px;">Height</th>
                <th rowspan="2" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 80px; white-space: normal; word-wrap: break-word;">Category</th>
                <th colspan="2" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; white-space: normal; word-wrap: break-word;">Supplier Name</th>
            </tr>
            <!-- Row 9 -->
            <tr>
                <th style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 80px; white-space: normal; word-wrap: break-word;">TOOLING</th>
                <th style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 45px; white-space: normal; word-wrap: break-word;">Dies</th>
                <th style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 45px; white-space: normal; word-wrap: break-word;">Jig</th>
                <th style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 45px; white-space: normal; word-wrap: break-word;">CF</th>
                <th style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 140px; min-width: 140px; white-space: normal; word-wrap: break-word;">Currency {{ !empty($currencyCode) ? "({$currencyCode})" : '' }}</th>
                <th style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; vertical-align: middle; font-weight: bold; width: 140px; min-width: 140px; white-space: normal; word-wrap: break-word;">IDR</th>
            </tr>

            <!-- ================= FILTER ROW (ROW 10) ================= -->
            <tr>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td colspan="6" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; width: 150px;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; width: 220px;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; width: 130px;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; width: 80px;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; width: 140px;">▼</td>
                <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; width: 140px;">▼</td>
            </tr>
        </thead>

        <!-- ================= DATA ROWS ================= -->
        <tbody>
            @php $currentRow = 11; @endphp
            @forelse($items as $index => $item)
                @php
                    $toolingProcs = $item->toolingProcesses ?? collect();
                    $addProcs = $item->addProcesses ?? collect();
                    $rowCount = max($toolingProcs->count(), 1);
                    $firstAddProcName = $addProcs->first()->process_name ?? '';
                @endphp

                @if($toolingProcs->count() > 0)
                    @foreach($toolingProcs as $tpIdx => $tp)
                        @php
                            $isLastRow = ($tpIdx === $toolingProcs->count() - 1);
                            $bStyle = $isLastRow ? 'border: 1px solid #000000; border-bottom: 1.5px solid #000000;' : 'border: 1px solid #000000;';
                            $rowNum = $currentRow++;
                        @endphp
                        <tr>
                            @if($tpIdx === 0)
                                <td rowspan="{{ $rowCount }}" style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 35px;">{{ $index + 1 }}</td>
                                <td colspan="6" rowspan="{{ $rowCount }}" style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 100px;">{{ $item->status ?: '' }}</td>
                                <td rowspan="{{ $rowCount }}" style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 150px;">{{ $item->part_no }}</td>
                                <td rowspan="{{ $rowCount }}" style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: left; vertical-align: middle; width: 220px;">{{ $item->part_name }}</td>
                                <td rowspan="{{ $rowCount }}" style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 130px;">{{ $item->mat_spec ?: '' }}</td>
                                <td rowspan="{{ $rowCount }}" style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 80px;">{{ $item->mat_thick ?: '' }}</td>
                            @endif

                            {{-- Process Name per row --}}
                            @php
                                $toolTypeStr = strtoupper(($tp->category ?? '') . ' ' . ($tp->tool_rank ?? '') . ' ' . ($tp->process_name ?? ''));
                                $isCfOrJigRow = (bool) preg_match('/(cf|jig)/i', $toolTypeStr);
                                $dieHeightVal = $isCfOrJigRow ? '' : ($tp->die_height ?: ($item->height ?: ''));
                                
                                $tStatus = trim($tp->tooling_status ?? '');
                                $tInfo = trim($tp->information ?? '');
                                $statusDisplay = (!empty($tStatus) && !empty($tInfo)) ? "{$tStatus} - {$tInfo}" : ($tStatus ?: $tInfo);
                            @endphp
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle; width: 130px;">{{ $tpIdx === 0 ? $firstAddProcName : '' }}</td>
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle; width: 80px;">{{ $tp->prod_homeline ?: '' }}</td>
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle;">{{ $statusDisplay }}</td>
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle;">{{ $tp->op ?: '' }}</td>
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle; width: 200px; min-width: 200px;">{{ $tp->process_name }}</td>
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle;">{{ $tp->category ?: ($tp->tool_rank ?: '') }}</td>
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle;">{{ (str_contains(strtoupper($tp->category ?? ''), 'DIE') || str_contains(strtoupper($tp->tool_rank ?? ''), 'DIE')) ? ($tp->qty ?: '') : '' }}</td>
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle;">{{ (str_contains(strtoupper($tp->category ?? ''), 'JIG') || str_contains(strtoupper($tp->tool_rank ?? ''), 'JIG')) ? ($tp->qty ?: '') : '' }}</td>
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle;">{{ (str_contains(strtoupper($tp->category ?? ''), 'CF') || str_contains(strtoupper($tp->tool_rank ?? ''), 'CF')) ? ($tp->qty ?: '') : '' }}</td>
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle;">{{ $tp->tonnage ?: '' }}</td>
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle;">{{ $dieHeightVal }}</td>
                            <td style="{{ $bStyle }} text-align: center; vertical-align: middle;"></td> {{-- Category Kosong --}}
                            <td style="{{ $bStyle }} text-align: right; vertical-align: middle; width: 140px;"></td> {{-- Currency Input Kosong --}}
                            <td style="{{ $bStyle }} text-align: right; vertical-align: middle; width: 140px;">=IF(ISNUMBER(X{{ $rowNum }}), X{{ $rowNum }} * $X$5, "")</td> {{-- Rumus IDR Otomatis --}}
                        </tr>
                    @endforeach
                @else
                    @php $rowNum = $currentRow++; @endphp
                    <tr>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 35px;">{{ $index + 1 }}</td>
                        <td colspan="6" style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 100px;">{{ $item->status ?: '' }}</td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 150px;">{{ $item->part_no }}</td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: left; vertical-align: middle; width: 220px;">{{ $item->part_name }}</td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 130px;">{{ $item->mat_spec ?: '' }}</td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 80px;">{{ $item->mat_thick ?: '' }}</td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 130px;">{{ $firstAddProcName }}</td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle; width: 80px;"></td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle;"></td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle;"></td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle;"></td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle;"></td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle;"></td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle;"></td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle;"></td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle;">{{ $item->height ?: '' }}</td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: center; vertical-align: middle;"></td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: right; vertical-align: middle; width: 140px;"></td>
                        <td style="border: 1px solid #000000; border-bottom: 1.5px solid #000000; text-align: right; vertical-align: middle; width: 140px;">=IF(ISNUMBER(X{{ $rowNum }}), X{{ $rowNum }} * $X$5, "")</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="25" style="border: 1.5px solid #000000; text-align: center; padding: 12px;">No EBD item data found.</td>
                </tr>
            @endforelse

            @if($currentRow > 11)
                @php $lastDataRow = $currentRow - 1; @endphp
                <tr>
                    <td colspan="17" style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: right; font-weight: bold; padding: 6px;">TOTAL</td>
                    <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; font-weight: bold;">=SUM(R11:R{{ $lastDataRow }})</td>
                    <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; font-weight: bold;">=SUM(S11:S{{ $lastDataRow }})</td>
                    <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; font-weight: bold;">=SUM(T11:T{{ $lastDataRow }})</td>
                    <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; font-weight: bold;"></td>
                    <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; font-weight: bold;"></td>
                    <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: center; font-weight: bold;"></td>
                    <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: right; font-weight: bold; width: 140px;">=SUM(X11:X{{ $lastDataRow }})</td>
                    <td style="background-color: #d9e1f2; border: 1.5px solid #000000; text-align: right; font-weight: bold; width: 140px;">=SUM(Y11:Y{{ $lastDataRow }})</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
