<?php

namespace App\Controllers;

use App\Models\M_Tiket;
use CodeIgniter\Controller;
use Config\Database;

class Dashboard extends Controller
{
    protected $ticketModel;
    protected $db;

    public function __construct()
    {
        helper(['url', 'session']);
        $this->ticketModel = new M_Tiket();
        $this->db = Database::connect();
    }

    public function index()
    {
        $session = session();
        $userId = $session->get('user_id');
        $idPegawai = $session->get('id_pegawai');

        if (!$userId) {
            return redirect('/');
        }

        $monthlySummary = $this->getMonthlyByAssignedTo($idPegawai);
        $statusChart = $this->getStatusTiketByAssignedToPerBulan($idPegawai); // diperbarui
        $avgRatings = $this->getAverageRatingsByAssignedTo($idPegawai);

        $totalTiketUser = $this->ticketModel
            ->where('id_pegawai_requestor', $idPegawai)
            ->countAllResults();

        $statusCounts = $this->ticketModel
            ->select('status, COUNT(*) as total')
            ->where('id_pegawai_requestor', $idPegawai)
            ->groupBy('status')
            ->findAll();

        $statusOrder = ['In Progress', 'Done', 'Closed'];
        $orderedStatusCounts = [];

        foreach ($statusOrder as $status) {
            $found = array_filter($statusCounts, fn($row) => $row['status'] === $status);
            $orderedStatusCounts[] = !empty($found) ? array_values($found)[0] : ['status' => $status, 'total' => 0];
        }

        $totalTiketUnresolved = $this->ticketModel
            ->where('id_pegawai_requestor', $idPegawai)
            ->whereIn('status', ['Open', 'In Progress'])
            ->countAllResults();

        $data = [
            'unit_kerja_id' => $session->get('unit_kerja_id'),
            'statusCounts' => $orderedStatusCounts,
            'totalTiketUser' => $totalTiketUser,
            'totalTiketToUnitF' => 0,
            'totalTiketToUnitG' => 0,
            'totalTiketUnresolved' => $totalTiketUnresolved,
            'bulanLabels' => $monthlySummary['labels'],
            'jumlahTiketBulan' => $monthlySummary['data'],
            'statusLabels' => $statusChart['labels'],
            'statusChartData' => $statusChart['data'],
            'avgTime' => $avgRatings['avg_time'],
            'avgService' => $avgRatings['avg_service'],
        ];

        return view('dashboard/index', $data);
    }

    private function getMonthlyByAssignedTo($idPegawai)
    {
        $builder = $this->db->table('tiket');
        $builder->select('MONTH(created_at) as bulan, COUNT(*) as total');
        $builder->where('YEAR(created_at)', date('Y'));
        $builder->where('assigned_to', $idPegawai);
        $builder->groupBy('MONTH(created_at)');
        $builder->orderBy('MONTH(created_at)');

        $result = $builder->get()->getResultArray();

        $labels = [];
        $data = array_fill(0, 12, 0);

        for ($i = 1; $i <= 12; $i++) {
            $labels[] = date('F', mktime(0, 0, 0, $i, 1));
        }

        foreach ($result as $row) {
            $index = (int) $row['bulan'] - 1;
            $data[$index] = $row['total'];
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getStatusTiketByAssignedToPerBulan($idPegawai)
    {
        $bulan = date('n');
        $tahun = date('Y');

        $builder = $this->db->table('tiket');
        $builder->select('status, COUNT(*) as total');
        $builder->where('assigned_to', $idPegawai);
        $builder->where('MONTH(created_at)', $bulan);
        $builder->where('YEAR(created_at)', $tahun);
        $builder->groupBy('status');

        $result = $builder->get()->getResultArray();

        $statusOrder = ['In Progress', 'Done', 'Closed'];
        $labels = $statusOrder;

        $data = [];
        foreach ($statusOrder as $status) {
            $found = array_filter($result, fn($row) => $row['status'] === $status);
            $data[$status] = !empty($found) ? (int) array_values($found)[0]['total'] : 0;
        }

        return [
            'labels' => $labels,
            'data' => array_values($data),
        ];
    }

    private function getAverageRatingsByAssignedTo($idPegawai)
    {
        $builder = $this->db->table('tiket');
        $builder->select('AVG(rating_time) as avg_time, AVG(rating_service) as avg_service');
        $builder->where('assigned_to', $idPegawai);
        $builder->where('rating_time IS NOT NULL');
        $builder->where('rating_service IS NOT NULL');

        $result = $builder->get()->getRowArray();

        return [
            'avg_time' => round((float) $result['avg_time'], 2),
            'avg_service' => round((float) $result['avg_service'], 2),
        ];
    }

    public function ticketTable()
    {
        $db = \Config\Database::connect();
        $idPegawai = session()->get('id_pegawai'); // Ambil ID pegawai dari session

        $builder = $db->table('tiket t');
        $builder->select('
        t.id_tiket,
        t.judul,
        t.status,
        t.created_at AS waktu_dibuat,
        t.updated_at AS waktu_selesai,
        t.prioritas,
        p.nama AS nama_requestor,
        uu.nm_unit_usaha AS nama_unit_usaha,
        k.nama_kategori,
        sk.nama_subkategori,
        petugas.nama AS nama_petugas,
        t.rating_time,
        t.rating_service
    ');

        // Join tabel yang relevan
        $builder->join('pegawai p', 't.id_pegawai_requestor = p.id_pegawai', 'left');
        $builder->join('pegawai petugas', 't.assigned_to = petugas.id_pegawai', 'left');
        $builder->join('unit_usaha uu', 't.unit_usaha_requestor = uu.id_unit_usaha', 'left');
        $builder->join('kategori k', 't.kategori_id = k.id_kategori', 'left');
        $builder->join('sub_kategori sk', 't.subkategori_id = sk.id_subkategori', 'left');

        // Filter hanya tiket yang assigned kepada pegawai login
        $builder->where('t.assigned_to', $idPegawai);

        // Urutkan dari yang terbaru
        $builder->orderBy('t.created_at', 'DESC');

        // Batasi hasil jika diperlukan
        $builder->limit(100);

        $data = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'data' => $data
        ]);
    }

    public function printReportPdf()
    {
        $request = service('request');
        $tanggalMulai = $request->getGet('tanggal_mulai');
        $tanggalSelesai = $request->getGet('tanggal_selesai');

        $session = session();
        $idPegawai = $session->get('id_pegawai');

        if (!$idPegawai) {
            return redirect()->to('/login')->with('error', 'Session expired. Silakan login kembali.');
        }

        $db = \Config\Database::connect();
        $builder = $db->table('tiket t');
        $builder->select('
        t.id_tiket,
        t.judul,
        t.status,
        t.created_at AS waktu_dibuat,
        t.updated_at AS waktu_selesai,
        t.prioritas,
        p.nama AS nama_requestor,
        uu.nm_unit_usaha AS nama_unit_usaha,
        k.nama_kategori,
        sk.nama_subkategori,
        petugas.nama AS nama_petugas,
        t.rating_time,
        t.rating_service,
        t.komentar_penyelesaian,
        t.komentar_staff,
        t.deskripsi
    ');
        $builder->join('pegawai p', 't.id_pegawai_requestor = p.id_pegawai', 'left');
        $builder->join('pegawai petugas', 't.assigned_to = petugas.id_pegawai', 'left');
        $builder->join('unit_usaha uu', 't.unit_usaha_requestor = uu.id_unit_usaha', 'left');
        $builder->join('kategori k', 't.kategori_id = k.id_kategori', 'left');
        $builder->join('sub_kategori sk', 't.subkategori_id = sk.id_subkategori', 'left');

        // Filter hanya tiket yang assigned kepada pegawai login
        $builder->where('t.assigned_to', $idPegawai);
        
        // Debug: log untuk memastikan filter bekerja
        log_message('info', 'Dashboard printReportPdf - ID Pegawai: ' . $idPegawai);
        log_message('info', 'Dashboard printReportPdf - Tanggal Mulai: ' . $tanggalMulai);
        log_message('info', 'Dashboard printReportPdf - Tanggal Selesai: ' . $tanggalSelesai);

        // Filter tanggal jika ada
        if (!empty($tanggalMulai)) {
            $builder->where('t.created_at >=', $tanggalMulai . ' 00:00:00');
        }
        if (!empty($tanggalSelesai)) {
            $builder->where('t.created_at <=', $tanggalSelesai . ' 23:59:59');
        }
        // Jika tidak ada filter tanggal, ambil semua data
        // if (empty($tanggalMulai) && empty($tanggalSelesai)) {
        //     $tanggalMulai = date('Y-m-d', strtotime('-1 month'));
        //     $builder->where('t.created_at >=', $tanggalMulai . ' 00:00:00');
        // }

        $builder->orderBy('t.created_at', 'DESC');
        $tickets = $builder->get()->getResultArray();
        
        // Debug: log jumlah data yang ditemukan
        log_message('info', 'Dashboard printReportPdf - Jumlah tiket ditemukan: ' . count($tickets));

        foreach ($tickets as &$row) {
            $row['waktu_dibuat'] = $row['waktu_dibuat'] ? date('Y-m-d H:i', strtotime($row['waktu_dibuat'])) : '-';
            $row['waktu_selesai'] = $row['waktu_selesai'] ? date('Y-m-d H:i', strtotime($row['waktu_selesai'])) : '-';
        }

        return $this->renderPdf($tickets, 'laporan_tiket_', 'dashboard');
    }



    private function renderPdf(array $tickets, string $filenamePrefix, string $sumber)
    {
        $session = session();
        $idPegawai = $session->get('id_pegawai');

        $penempatan = $this->db->table('pegawai_penempatan pp')
            ->select('uu.nm_unit_usaha')
            ->join('unit_usaha uu', 'pp.id_unit_usaha = uu.id_unit_usaha', 'left')
            ->where('pp.id_pegawai', $idPegawai)
            ->get()
            ->getRow();

        $namaUnitUsaha = $penempatan->nm_unit_usaha ?? '-';

        $html = view('tickets/pdf_report', [
            'tickets' => $tickets,
            'namaUnitUsaha' => $namaUnitUsaha,
            'sumber' => $sumber // kirim ke view
        ]);

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = $filenamePrefix . date('Ymd_His') . '.pdf';
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($dompdf->output());
    }

}
