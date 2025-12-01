<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Tiket - Helpdesk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 40px;
            margin-top: 20px;
        }

        header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #003366;
            padding-bottom: 5px;
        }

        h3 {
            color: #003366;
            margin-top: 10px;
            /* sebelumnya 30px */
        }

        h1 {
            margin: 0;
            font-size: 18px;
            color: #003366;
        }

        h2 {
            color: #003366;
            margin-top: 30px;
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
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f1f5f9;
            color: #003366;
        }

        .label {
            font-weight: bold;
            width: 180px;
            background-color: #f9fafb;
            color: #003366;
        }

        .img-lampiran {
            max-width: 250px;
            border: 1px solid #ccc;
            margin-top: 10px;
        }

        footer {
            text-align: right;
            font-size: 11px;
            margin-top: 40px;
            color: #666;
        }

        footer img {
            display: block;
            margin: 0 auto 10px auto;
        }

        #kop-logo {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }

        #kop-logo img {
            height: 60px;
        }
    </style>
</head>


<body>

    <header>
        <h1>Helpdesk - <?= esc($ticket['req_penempatan']['unit_usaha']) ?></h1>
    </header>

    <h3>Tiket #<?= esc($ticket['id_tiket']) ?></h3>

    <h3>Informasi Requestor & Penempatan</h3>
    <table>
        <tr>
            <td class="label">Nama Requestor</td>
            <td><?= esc($ticket['requestor_nama']) ?></td>
        </tr>
        <tr>
            <td class="label">Telepon</td>
            <td><?= esc($ticket['requestor_telpon1'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td><?= esc($ticket['requestor_email'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Unit Level</td>
            <td><?= esc($ticket['req_penempatan']['unit_level'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Unit Usaha</td>
            <td><?= esc($ticket['req_penempatan']['unit_usaha'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Unit Kerja</td>
            <td><?= esc($ticket['req_penempatan']['unit_kerja'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Unit Kerja Sub</td>
            <td><?= esc($ticket['req_penempatan']['unit_kerja_sub'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="label">Unit Lokasi</td>
            <td><?= esc($ticket['req_penempatan']['unit_lokasi'] ?? '-') ?></td>
        </tr>
    </table>

    <h3>Informasi Tiket</h3>
    <table>
        <tr>
            <td class="label">Kategori</td>
            <td><?= esc($ticket['kategori']) ?></td>
        </tr>
        <tr>
            <td class="label">Subkategori</td>
            <td><?= esc($ticket['subkategori']) ?></td>
        </tr>
        <tr>
            <td class="label">Judul</td>
            <td><?= esc($ticket['judul']) ?></td>
        </tr>
        <tr>
            <td class="label">Deskripsi</td>
            <td><?= $ticket['deskripsi'] ?></td>
        </tr>
        <?php if (!empty($ticket['gambar'])): ?>
            <tr>
                <td class="label">Gambar</td>
                <td><img class="img-lampiran" src="<?= site_url('uploads/' . $ticket['gambar']) ?>" alt="Lampiran Tiket">
                </td>
            </tr>
        <?php endif ?>
        <tr>
            <td class="label">Prioritas</td>
            <td><?= esc($ticket['prioritas']) ?></td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td><?= esc($ticket['status']) ?></td>
        </tr>
        <tr>
            <td class="label">Tanggal Dibuat</td>
            <td><?= esc($ticket['created_at']) ?></td>
        </tr>
    </table>

    <h3>Riwayat Petugas</h3>
    <?php if (!empty($ticket['assignees'])): ?>
        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">No</th>
                    <th style="text-align: center;">Nama Petugas</th>
                    <th style="min-width: 60px; text-align: center;">Waktu Mulai</th>
                    <th style="min-width: 60px; text-align: center;">Waktu Selesai</th>
                    <th style="text-align: center;">Durasi</th>
                    <th style="text-align: center;">Komentar Petugas</th>
                    <th style="text-align: center;">Komentar Feedback</th>
                    <th style="text-align: center;">Rating Waktu</th>
                    <th style="text-align: center;">Rating Layanan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ticket['assignees'] as $i => $a):
                    $dura = (!empty($a['assigned_at']) && !empty($a['finished_at']))
                        ? formatDurasi($a['assigned_at'], $a['finished_at'])
                        : '-';
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= esc($a['assigned_nama'] ?? '-') ?></td>
                        <td><?= esc($a['assigned_at'] ?? '-') ?></td>
                        <td><?= esc($a['finished_at'] ?? '-') ?></td>
                        <td><?= $dura ?></td>
                        <td><?= esc($a['komentar_staff'] ?? '-') ?></td>
                        <td><?= esc($a['komentar_penyelesaian'] ?? '-') ?></td>
                        <td><?= ratingTimeText($a['rating_time'] ?? '-') ?></td>
                        <td><?= ratingServiceText($a['rating_service'] ?? '-') ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><em>Belum ada riwayat petugas.</em></p>
    <?php endif ?>

    <footer>
        Dicetak pada: <?= date('d/m/Y H:i') ?>
    </footer>
</body>

</html>

<?php
function formatDurasi($startRaw, $endRaw)
{
    try {
        $start = new DateTime($startRaw);
        $end = new DateTime($endRaw);
        $interval = $start->diff($end);

        $jam = (int) $interval->h + ($interval->d * 24);
        $menit = (int) $interval->i;
        $detik = (int) $interval->s;

        if ($jam === 0 && $menit === 0 && $detik === 0)
            return '0 detik';

        $parts = [];
        if ($jam > 0)
            $parts[] = $jam . ' jam';
        if ($menit > 0)
            $parts[] = $menit . ' mnt';
        if ($detik > 0)
            $parts[] = $detik . ' dtk';

        return implode(' ', $parts);
    } catch (Exception $e) {
        return '-';
    }
}

function ratingTimeText($rating)
{
    switch (intval($rating)) {
        case 1:
            return "Sangat Lambat";
        case 2:
            return "Lambat";
        case 3:
            return "Cukup";
        case 4:
            return "Cepat";
        case 5:
            return "Sangat Cepat";
        default:
            return "-";
    }
}

function ratingServiceText($rating)
{
    switch (intval($rating)) {
        case 1:
            return "Sangat Buruk";
        case 2:
            return "Buruk";
        case 3:
            return "Cukup";
        case 4:
            return "Baik";
        case 5:
            return "Sangat Baik";
        default:
            return "-";
    }
}
?>