<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Tiket - Helpdesk</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 25px 40px 40px 20px; /* Atas, Kanan, Bawah, Kiri */
        }

        header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #003366;
            padding-bottom: 5px;
        }

        h1 {
            margin: 0;
            font-size: 18px;
            color: #003366;
        }

        h2 {
            color: #003366;
            margin-top: 30px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 30px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
            font-size: 12px;
        }

        th {
            background-color: #f1f5f9;
            color: #003366;
        }

        footer {
    text-align: right;
    font-size: 11px;
    margin-top: 10px; /* sebelumnya 40px */
    color: #666;
}
    </style>
</head>

<body>
    <header>
        <h1>Report Tiket - Helpdesk</h1>
        <?php if (isset($sumber) && $sumber === 'dashboard'): ?>
            <?php 
            $session = session();
            $userName = $session->get('nama');
            if ($userName): ?>
                <p style="margin: 5px 0; font-size: 14px; color: #666;">Petugas: <?= esc($userName) ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <?php 
        $request = service('request');
        $tanggalMulai = $request->getGet('tanggal_mulai');
        $tanggalSelesai = $request->getGet('tanggal_selesai');
        if ($tanggalMulai || $tanggalSelesai): ?>
            <p style="margin: 5px 0; font-size: 14px; color: #666;">
                Periode: 
                <?= $tanggalMulai ? date('d/m/Y', strtotime($tanggalMulai)) : 'Semua' ?> 
                - 
                <?= $tanggalSelesai ? date('d/m/Y', strtotime($tanggalSelesai)) : 'Semua' ?>
            </p>
        <?php endif; ?>
    </header>
    <footer>
        Dicetak pada: <?= date('d/m/Y H:i') ?>
    </footer>


    <table>
        <thead>
            <tr>
                <th style="width: 3%; text-align: center;">No</th>
                <th style="width: 8%; text-align: center;">ID Tiket</th>
                <th style="width: 10%; text-align: center;">Requestor</th>
                <th style="width: 10%; text-align: center;">Unit Usaha</th>
                <th style="width: 15%; text-align: center;">Judul</th>
                <th style="width: 10%; text-align: center;">Kategori</th>
                <th style="width: 10%; text-align: center;">SubKategori</th>
                <th style="min-width: 70px; text-align: center;">Waktu Dibuat</th>
                <th style="min-width: 70px; text-align: center;">Waktu Selesai</th>
                <th style="width: 7%; text-align: center;">Prioritas</th>
                <th style="width: 7%; text-align: center;">Status</th>
                <th style="width: 10%; text-align: center;">Petugas</th>
                <th style="width: 10%; text-align: center;">Rating Waktu</th>
                <th style="width: 10%; text-align: center;">Rating Service</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($tickets)): ?>
                <?php $no = 1;
                foreach ($tickets as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= esc($row['id_tiket']) ?></td>
                        <td><?= esc($row['nama_requestor'] ?? '-') ?></td>
                        <td><?= esc($row['nama_unit_usaha'] ?? '-') ?></td>
                        <td><?= esc($row['judul']) ?></td>
                        <td><?= esc($row['nama_kategori'] ?? '-') ?></td>
                        <td><?= esc($row['nama_subkategori'] ?? '-') ?></td>
                        <td><?= esc($row['waktu_dibuat']) ?></td>
                        <td><?= esc($row['waktu_selesai']) ?></td>
                        <td><?= esc($row['prioritas']) ?></td>
                        <td><?= esc($row['status']) ?></td>
                        <td><?= esc($row['nama_petugas'] ?? '-') ?></td>
                        <td><?= esc($row['rating_time'] ?? '-') ?></td>
                        <td><?= esc($row['rating_service'] ?? '-') ?></td>
                    </tr>
                <?php endforeach ?>
            <?php else: ?>
                <tr>
                    <td colspan="14" style="text-align:center;">Tidak ada data</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>


</body>


</html>