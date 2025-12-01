<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\M_Tiket;
use App\Models\M_Tiket_Assigned;
use Config\Database;
use Carbon\Carbon;
use Config\Services;
use Dompdf\Dompdf;
use Dompdf\Options;

class Tickets extends Controller
{
    protected $ticketModel;
    public $appTimezone = 'Asia/Jakarta';
    protected $db;

    protected function sendEmailToUnitTujuan($ticketData, $type = null)
    {
        if (!is_array($ticketData)) {
            log_message('error', 'Parameter ticketData harus berupa array.');
            return;
        }

        $builder = $this->db->table('pegawai_penempatan pp');
        $builder->select('u.email, u.nama');
        $builder->join('user u', 'u.id_pegawai = pp.id_pegawai');

        $builder->groupStart();

        // 1. Jika id_unit_kerja_sub_tujuan = F39 → kirim ke pegawai C1, F39
        if ($ticketData['id_unit_kerja_sub_tujuan'] === 'F39') {
            $builder->orGroupStart();
            $builder->where('pp.id_unit_kerja', 'E15');
            $builder->where('pp.id_unit_kerja_sub', 'F39');
            $builder->groupEnd();
        }

        // 2. Jika unit_bisnis_requestor = B2, tujuan E15, sub tujuan != F39, kirim ke E15 & unit usaha sama
        if (
            $ticketData['unit_bisnis_requestor'] === 'B2' &&
            $ticketData['id_unit_tujuan'] === 'E15' &&
            $ticketData['id_unit_kerja_sub_tujuan'] !== 'F39'
        ) {
            $builder->orGroupStart();
            $builder->where('pp.id_unit_kerja', 'E15');
            $builder->where('pp.id_unit_usaha', $ticketData['unit_usaha_requestor']);
            $builder->groupEnd();
        }

        // 3. Jika unit_bisnis_requestor = B3, tujuan E15, sub tujuan != F39 → C1, F40
        if (
            $ticketData['unit_bisnis_requestor'] === 'B3' &&
            $ticketData['id_unit_tujuan'] === 'E15' &&
            $ticketData['id_unit_kerja_sub_tujuan'] !== 'F39'
        ) {
            $builder->orGroupStart();
            $builder->where('pp.id_unit_kerja_sub', 'F40');
            $builder->where('pp.id_unit_usaha', 'C1');
            $builder->groupEnd();
        }

        // 4. Jika unit_bisnis_requestor = B1, tujuan E15, sub tujuan != F39 → C1, F38
        if (
            $ticketData['unit_bisnis_requestor'] === 'B1' &&
            $ticketData['id_unit_tujuan'] === 'E15' &&
            $ticketData['id_unit_kerja_sub_tujuan'] !== 'F39' &&
            $ticketData['unit_usaha_requestor'] === 'C1'
        ) {
            $builder->orGroupStart();
            $builder->where('pp.id_unit_kerja_sub', 'F38');
            $builder->where('pp.id_unit_usaha', 'C1');
            $builder->groupEnd();
        }

        // 5. Jika id_unit_tujuan = E21 → kirim ke pegawai dengan unit_usaha E21, sub unit kerja F45, dan unit_kerja yang sama dengan requestor 
        if ($ticketData['id_unit_tujuan'] === 'E21') {
            $builder->orGroupStart();
            $builder->where('pp.id_unit_kerja', 'E21');
            $builder->where('pp.id_unit_kerja_sub', 'F45');
            $builder->where('pp.id_unit_usaha', $ticketData['unit_usaha_requestor']);
            $builder->groupEnd();
        }

        // 6. Jika unit_bisnis_requestor = B3 dan id_unit_tujuan = E21 → kirim ke E21, F45, C1
        if (
            $ticketData['unit_bisnis_requestor'] === 'B3' &&
            $ticketData['id_unit_tujuan'] === 'E21'
        ) {
            $builder->orGroupStart();
            $builder->where('pp.id_unit_kerja', 'E21');
            $builder->where('pp.id_unit_kerja_sub', 'F45');
            $builder->where('pp.id_unit_usaha', 'C1');
            $builder->groupEnd();
        }

        $builder->groupEnd();

        $emails = $builder->get()->getResultArray();

        foreach ($emails as $user) {
            $emailService = \Config\Services::email();
            $emailService->clear();
            $emailService->setTo($user['email']);

            if ($type === 'belum_selesai') {
                $emailService->setSubject("Tiket Belum Selesai: " . $ticketData['judul']);
                $emailService->setMessage("Halo {$user['nama']},\n\nTiket dengan judul \"{$ticketData['judul']}\" telah dikembalikan oleh requestor karena dianggap belum selesai.\n\nMohon untuk ditindaklanjuti kembali.");
            } else {
                $emailService->setSubject("Tiket Baru Masuk: " . $ticketData['judul']);
                $emailService->setMessage("Halo {$user['nama']},\n\nAda tiket baru yang masuk ke unit Anda dengan judul:\n\n{$ticketData['judul']}\n\nSilakan cek sistem untuk detail lebih lanjut.");
            }

            $emailService->send();
        }
    }




    protected function sendEmailToRequestor($requestorEmail, $ticketData, $subject, $message)
    {
        $emailService = Services::email();

        $emailService->clear();
        $emailService->setTo($requestorEmail);
        $emailService->setSubject($subject);
        $emailService->setMessage($message);
        $emailService->setMailType('html');
        $emailService->send();
    }


    public function __construct()
    {
        helper(['form', 'url', 'session']);
        $this->ticketModel = new M_Tiket();
        $this->db = Database::connect();

        // ✅ Set timezone untuk semua proses waktu
        date_default_timezone_set('Asia/Jakarta');
        Carbon::setLocale('id');
    }

    public function index()
    {
        return view('tickets/index');
    }

    public function createView()
    {
        $session = session();
        $unitUsaha = $session->get('unit_usaha_id');

        $kategori = $this->db->table('kategori')->get()->getResultArray();
        $subkategori = $this->db->table('sub_kategori as sk')
            ->join('kategori as k', 'sk.id_kategori = k.id_kategori')
            ->get()->getResultArray();

        $allowedUnitIds = ['E15', 'E21'];

        $unitsRaw = $this->db->table('unit_kerja')
            ->whereIn('id_unit_kerja', $allowedUnitIds)
            ->get()
            ->getResultArray();

        // Mapping nama sesuai ID
        $units = [];
        foreach ($unitsRaw as $unit) {
            if ($unit['id_unit_kerja'] == 'E15') {
                $unit['nm_unit_kerja'] = 'Information Technology';
            } elseif ($unit['id_unit_kerja'] == 'E21') {
                $unit['nm_unit_kerja'] = 'General Affair';
            }
            $units[] = $unit;
        }

        return view('tickets/create', [
            'units' => $units,
            'kategori' => $kategori,
            'subkategori' => $subkategori,
        ]);
    }

    public function create()
    {
        $session = session();
        $idPegawaiRequestor = $session->get('id_pegawai');

        $penempatan = $this->db->table('pegawai_penempatan as pp')
            ->select('pp.id_unit_level, pp.id_unit_bisnis, pp.id_unit_usaha, pp.id_unit_organisasi, pp.id_unit_kerja, pp.id_unit_kerja_sub, pp.id_unit_lokasi')
            ->where('pp.id_pegawai', $idPegawaiRequestor)
            ->get()->getRow();

        if (!$penempatan) {
            return redirect()->back()->with('error', 'Data penempatan requestor tidak ditemukan.');
        }

        $validation = \Config\Services::validation();
        $rules = [
            'judul' => 'required|max_length[255]',
            'deskripsi' => 'required',
            'id_unit_tujuan' => 'required',
            'kategori' => 'required',
            'subkategori' => 'required',
            'gambar' => 'permit_empty|is_image[gambar]|max_size[gambar,2048]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $fileName = null;
        if ($file = $this->request->getFile('gambar')) {
            if ($file->isValid() && !$file->hasMoved()) {
                $fileName = $file->getRandomName();
                $file->move(WRITEPATH . 'uploads', $fileName);
            }
        }

        $kategoriId = $this->request->getPost('kategori');
        $kategori = $this->db->table('kategori')
            ->select('penanggung_jawab')
            ->where('id_kategori', $kategoriId)
            ->get()->getRow();

        $idUnitKerjaSubTujuan = null;
        if ($kategori) {
            $pj = json_decode($kategori->penanggung_jawab ?? '[]', true);
            if (!empty($pj)) {
                $idUnitKerjaSubTujuan = $pj[0]; // ambil penanggung jawab pertama
            }
        }

        // Prioritas default: '-' jika belum ada yang ambil
        $prioritas = '-';

        $data = [
            'id_tiket' => $this->ticketModel->generateIdTiket(),
            'id_pegawai_requestor' => $idPegawaiRequestor,
            'unit_level_requestor' => $penempatan->id_unit_level ?? null,
            'unit_bisnis_requestor' => $penempatan->id_unit_bisnis ?? null,
            'unit_usaha_requestor' => $penempatan->id_unit_usaha ?? null,
            'unit_organisasi_requestor' => $penempatan->id_unit_organisasi ?? null,
            'unit_kerja_requestor' => $penempatan->id_unit_kerja ?? null,
            'unit_kerja_sub_requestor' => $penempatan->id_unit_kerja_sub ?? null,
            'unit_lokasi_requestor' => $penempatan->id_unit_lokasi ?? null,
            'judul' => $this->request->getPost('judul'),
            'deskripsi' => $this->request->getPost('deskripsi'),
            'id_unit_tujuan' => $this->request->getPost('id_unit_tujuan'),
            'id_unit_kerja_sub_tujuan' => $this->request->getPost('id_unit_kerja_sub_tujuan'),
            'kategori_id' => $this->request->getPost('kategori'),
            'subkategori_id' => $this->request->getPost('subkategori'),
            'id_ruangan' => $penempatan->id_unit_kerja_sub,
            'prioritas' => 'Low',
            'komentar_staff' => null,
            'gambar' => $fileName,
            'status' => 'Open',
        ];

        $this->ticketModel->insert($data);
        //$this->sendEmailToUnitTujuan($data);
        return redirect()->to('/tickets')->with('success', 'Tiket berhasil dibuat dan dikirim ke unit terkait.');
    }


    public function list()
    {
        $request = service('request');
        $session = session();

        $start = (int) ($request->getGet('start') ?? 0);
        $length = (int) ($request->getGet('length') ?? 10);
        $draw = (int) ($request->getGet('draw') ?? 1);
        $searchValue = $request->getGet('search')['value'] ?? '';

        $orderColumnIndex = $request->getGet('order')[0]['column'] ?? 0;
        $orderDir = $request->getGet('order')[0]['dir'] ?? 'asc';

        $idPegawai = $session->get('id_pegawai');

        // Sesuaikan urutan ini dengan urutan kolom di DataTables JS
        $columns = [
            'uk.nm_unit_kerja',     // index 0
            't.judul',              // index 1
            'k.nama_kategori',      // index 2
            'sk.nama_subkategori',  // index 3
            't.prioritas',          // index 4
            't.status',             // index 5
            't.created_at',         // index 6
        ];

        $builder = $this->db->table('tiket t');
        $builder->select('
        t.id_tiket, 
        t.judul, 
        k.nama_kategori, 
        sk.nama_subkategori, 
        t.prioritas, 
        t.status, 
        t.created_at, 
        t.confirm_by_requestor, 
        uk.id_unit_kerja as tujuan,
        uk.nm_unit_kerja as nama_tujuan_unit
    ');
        $builder->join('kategori k', 't.kategori_id = k.id_kategori', 'left');
        $builder->join('sub_kategori sk', 't.subkategori_id = sk.id_subkategori', 'left');
        $builder->join('unit_kerja uk', 't.id_unit_tujuan = uk.id_unit_kerja', 'left');
        $builder->where('t.id_pegawai_requestor', $idPegawai);

        $totalData = $builder->countAllResults(false);

        if (!empty($searchValue)) {
            $builder->groupStart()
                ->like('t.judul', $searchValue)
                ->orLike('t.id_tiket', $searchValue)
                ->orLike('uk.nm_unit_kerja', $searchValue)
                ->orLike('t.deskripsi', $searchValue)
                ->orLike('k.nama_kategori', $searchValue)
                ->orLike('sk.nama_subkategori', $searchValue)
                ->orLike('uk.nm_unit_kerja', $searchValue)
                ->orLike('t.prioritas', $searchValue)
                ->orLike('t.status', $searchValue)
                ->groupEnd();

            if (strtolower($searchValue) === 'it') {
                $builder->orWhere('uk.id_unit_kerja', 'E15');
            } elseif (strtolower($searchValue) === 'ga') {
                $builder->orWhere('uk.id_unit_kerja', 'E21');
            }
        }

        $totalFiltered = $builder->countAllResults(false);

        // Sorting dinamis berdasarkan kolom DataTables
        if (isset($columns[$orderColumnIndex])) {
            $builder->orderBy($columns[$orderColumnIndex], $orderDir);
        } else {
            $builder->orderBy('t.created_at', 'DESC');
        }

        // Pagination dan ambil data
        $data = $builder->limit($length, $start)->get()->getResultArray();

        // ✅ Mapping tujuan
        $unitMapping = [
            'E15' => 'IT',
            'E21' => 'GA'
        ];

        foreach ($data as &$row) {
            if (isset($unitMapping[$row['tujuan']])) {
                $row['tujuan'] = $unitMapping[$row['tujuan']];
            }
        }
        unset($row); // good practice

        return $this->response->setJSON([
            "draw" => $draw,
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $data,
        ]);
    }

    public function listForUnit()
    {
        $request = $this->request;
        $unitUsahaFilter = $request->getGet('unit_usaha');
        $tanggalMulai = $request->getGet('tanggal_mulai');
        $tanggalSelesai = $request->getGet('tanggal_selesai');
        $status = $request->getGet('status');
        $orderParam = strtolower($request->getGet('order') ?? ''); // ambil pilihan urutan dari user

        $session = session();
        $idPegawai = $session->get('id_pegawai');

        // Cek penempatan pegawai
        $penempatan = $this->db->table('pegawai_penempatan')
            ->where('id_pegawai', $idPegawai)
            ->get()
            ->getRow();

        if (!$penempatan) {
            return $this->response->setJSON(['error' => 'Penempatan pegawai tidak ditemukan']);
        }

        // Build query tiket
        $builder = $this->db->table('tiket t');
        $builder->select('t.*, t.id_tiket, t.unit_usaha_requestor, u.nama as assigned_nama, ur.nama as requestor_nama, uu.nm_unit_usaha, ul.nm_unit_level');
        $builder->join('user u', 'u.id_pegawai = t.assigned_to', 'left');
        $builder->join('user ur', 'ur.id_pegawai = t.id_pegawai_requestor', 'left');
        $builder->join('unit_usaha uu', 'uu.id_unit_usaha = t.unit_usaha_requestor', 'left');
        $builder->join('unit_level ul', 'ul.id_unit_level = t.unit_level_requestor', 'left');
        $builder->where('status', $status);

        // Filter akses berdasarkan penempatan
        $builder->groupStart();

        // 1. C1 + F39
        if ($penempatan->id_unit_usaha === 'C1' && $penempatan->id_unit_kerja_sub === 'F39') {
            $builder->orGroupStart()
                ->where('t.id_unit_kerja_sub_tujuan', 'F39')
                ->groupEnd();
        }

        // 2. B2 → E15 (bukan F39)
        if ($penempatan->id_unit_usaha && $penempatan->id_unit_kerja) {
            $builder->orGroupStart()
                ->where('t.unit_bisnis_requestor', 'B2')
                ->where('t.id_unit_tujuan', 'E15')
                ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                ->where('t.id_unit_tujuan', $penempatan->id_unit_kerja)
                ->groupEnd();
        }

        // 3. B3 → E15 (C1 + F40 saja)
        if ($penempatan->id_unit_usaha === 'C1' && $penempatan->id_unit_kerja_sub === 'F40') {
            $builder->orGroupStart()
                ->where('t.unit_bisnis_requestor', 'B3')
                ->where('t.id_unit_tujuan', 'E15')
                ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                ->groupEnd();
        }

        // 4. B1 → E15 (C1 + F38)
        if ($penempatan->id_unit_usaha === 'C1' && $penempatan->id_unit_kerja_sub === 'F38') {
            $builder->orGroupStart()
                ->where('t.unit_bisnis_requestor', 'B1')
                ->where('t.id_unit_tujuan', 'E15')
                ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                ->where('t.unit_usaha_requestor', 'C1')
                ->groupEnd();
        }

        // 5. E21 akses langsung
        if ($penempatan->id_unit_usaha && $penempatan->id_unit_kerja) {
            $builder->orGroupStart()
                ->where('t.id_unit_tujuan', 'E21')
                ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                ->where('t.id_unit_tujuan', $penempatan->id_unit_kerja)
                ->groupEnd();
        }

        // 6. C1 + E21 → tiket dari B3 dengan tujuan E21 dan unit_usaha_requestor & id_unit_tujuan sesuai penempatan
        if ($penempatan->id_unit_usaha === 'C1' && $penempatan->id_unit_kerja === 'E21') {
            $builder->orGroupStart()
                ->where('t.unit_bisnis_requestor', 'B3')
                ->where('t.id_unit_tujuan', 'E21')
                ->groupEnd();
        }

        // 7. Tiket Closed yang pernah ditugaskan
        $builder->orGroupStart()
            ->where('t.status', 'Closed')
            ->where('t.assigned_to', $idPegawai);

        if (!empty($unitUsahaFilter)) {
            $builder->where('t.unit_usaha_requestor', $unitUsahaFilter);
        }

        $builder->groupEnd();
        $builder->groupEnd(); // end all OR filter kelompok

        // Tambahkan filter status (wajib)
        if (!empty($status)) {
            $builder->where('t.status', $status);
        }

        // Filter tanggal jika status = Done/Closed dan tanggal mulai ada
        if (in_array($status, ['Done', 'Closed']) && !empty($tanggalMulai)) {
            $builder->where('DATE(t.created_at) >=', $tanggalMulai);
            if (!empty($tanggalSelesai)) {
                $builder->where('DATE(t.created_at) <=', $tanggalSelesai);
            }
        }

        // Tentukan urutan
        if (in_array($orderParam, ['asc', 'desc'])) {
            $orderDir = strtoupper($orderParam);
        } else {
            $orderDir = ($status === 'Open' || $status === 'In Progress') ? 'ASC' : 'DESC';
        }
        $builder->orderBy('t.created_at', $orderDir);

        // Ambil data
        $tickets = $builder->get()->getResultArray();

        // Format hasil
        foreach ($tickets as &$ticket) {
            $ticket['created_at'] = \Carbon\Carbon::parse($ticket['created_at'])
                ->locale('id')->isoFormat('D MMMM YYYY HH:mm');
            $ticket['can_take'] = true;
        }

        return $this->response->setJSON([
            "draw" => (int) $request->getGet('draw'),
            "recordsTotal" => count($tickets),
            "recordsFiltered" => count($tickets),
            "data" => $tickets,
        ]);
    }



    public function takeTicket()
    {
        $session = session();
        $idPegawai = $session->get('id_pegawai');
        $idTiket = $this->request->getPost('id_tiket');
        $status = $this->request->getPost('status') ?? 'In Progress';
        $komentarPenyelesaian = $this->request->getPost('komentar_penyelesaian') ?? null;
        $prioritas = $this->request->getPost('prioritas') ?? null;
        $komentarStaff = $this->request->getPost('komentar_staff') ?? null;

        $assignedModel = new M_Tiket_Assigned();

        $ticket = $this->ticketModel->find($idTiket);
        if (!$ticket) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tiket tidak ditemukan']);
        }

        // Cek jika tiket sudah diambil orang lain
        if ($ticket['assigned_to'] && $ticket['assigned_to'] != $idPegawai) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tiket sudah diambil oleh orang lain']);
        }

        // Validasi komentar jika status = Done
        if ($status === 'Done' && (empty($komentarStaff) || trim($komentarStaff) === '')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Komentar staff wajib diisi jika status tiket Done']);
        }

        // Tentukan sequence assignment
        $lastAssignee = $assignedModel->where('id_tiket', $idTiket)
            ->orderBy('sequence', 'DESC')
            ->first();
        $sequence = $lastAssignee ? $lastAssignee['sequence'] + 1 : 1;

        // Tentukan waktu assigned_at
        $assignedAt = ($status === 'Done') ? ($ticket['created_at'] ?? date('Y-m-d H:i:s')) : date('Y-m-d H:i:s');

        // Simpan assignment baru
        $assignedModel->insert([
            'id_tiket' => $idTiket,
            'assigned_to' => $idPegawai,
            'sequence' => $sequence,
            'assigned_at' => $assignedAt
        ]);

        $insertedId = $assignedModel->getInsertID();

        if (!empty($komentarStaff)) {
            $assignedModel->update($insertedId, ['komentar_staff' => $komentarStaff]);
        }

        // Siapkan data update tiket
        $updateData = [
            'assigned_to' => $idPegawai,
            'status' => $status,
            'komentar_penyelesaian' => $komentarPenyelesaian,
            'updated_at' => date('Y-m-d H:i:s'),
            // komentar_staff tetap disimpan di tabel assignment, bukan tiket
        ];

        if (in_array($prioritas, ['High', 'Medium', 'Low'])) {
            $updateData['prioritas'] = $prioritas;
        }

        // Jika status langsung Done
        if ($status === 'Done') {
            $finishedAt = date('Y-m-d H:i:s');
            $updateData['finished_at'] = $finishedAt;

            // Simpan komentar staff dan waktu selesai ke table ticket_assignees
            $assignedModel->update($insertedId, [
                'finished_at' => $finishedAt,
                'komentar_staff' => $komentarStaff
            ]);
        }

        // Update tiket
        $this->ticketModel->update($idTiket, $updateData);

        // Kirim notifikasi ke requestor
        $requestor = $this->db->table('user')
            ->select('email, nama')
            ->where('id_pegawai', $ticket['id_pegawai_requestor'])
            ->get()->getRow();

        if ($requestor) {
            if ($status === 'In Progress') {
                $subject = "Tiket Anda Sedang Dalam Proses";
                $message = "Halo {$requestor->nama},\n\nTiket dengan judul \"{$ticket['judul']}\" telah diambil dan sedang dalam proses pengerjaan.";
            } elseif ($status === 'Done') {
                $subject = "Tiket Anda Telah Selesai Dikerjakan";
                $message = "Halo {$requestor->nama},\n\nTiket dengan judul \"{$ticket['judul']}\" telah selesai dikerjakan. \n\nSilakan cek dan konfirmasi.";
            }
            //$this->sendEmailToRequestor($requestor->email, $ticket, $subject, $message);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Tiket berhasil diambil dan diperbarui']);
    }



    public function finish()
    {
        $session = session();
        $userId = $session->get('id_pegawai');
        $idTiket = $this->request->getPost('id_tiket');

        $ticket = $this->ticketModel->find($idTiket);

        if (!$ticket) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tiket tidak ditemukan']);
        }

        if ($ticket['assigned_to'] !== $userId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Anda tidak berhak mengubah status tiket ini']);
        }

        if ($ticket['status'] !== 'In Progress') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Status tiket bukan In Progress']);
        }

        // Simpan waktu selesai
        $finishedAt = date('Y-m-d H:i:s');

        // Update tiket utama
        $this->ticketModel->update($idTiket, [
            'status' => 'Done',
            'updated_at' => $finishedAt,
            'finished_at' => $finishedAt
        ]);

        // Ambil id record terakhir dari tiket_assignees
        $lastAssigned = $this->db->table('ticket_assignees')
            ->select('id')
            ->where('id_tiket', $idTiket)
            ->where('assigned_to', $userId)
            ->orderBy('assigned_at', 'DESC')
            ->get()
            ->getRow();

        if ($lastAssigned) {
            // Update record tersebut
            $this->db->table('ticket_assignees')
                ->where('id', $lastAssigned->id)
                ->update(['finished_at' => $finishedAt]);
        }

        // Kirim notifikasi ke requestor
        $requestor = $this->db->table('user')
            ->select('email, nama')
            ->where('id_pegawai', $ticket['id_pegawai_requestor'])
            ->get()
            ->getRow();

        if ($requestor) {
            $subject = "Tiket Anda Telah Selesai Dikerjakan";
            $message = "Halo {$requestor->nama},\n\nTiket dengan judul \"{$ticket['judul']}\" telah selesai dikerjakan. Silakan cek dan konfirmasi.";
            //$this->sendEmailToRequestor($requestor->email, $ticket, $subject, $message);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Status tiket berhasil diubah menjadi Done']);
    }



    public function confirmCompletion()
    {
        $idTiket = $this->request->getPost('id_tiket');
        $statusKonfirmasi = $this->request->getPost('status'); // Closed / Open
        $komentar = $this->request->getPost('komentar_penyelesaian') ?? null;
        $ratingTime = $this->request->getPost('rating_time') ?? null;
        $ratingService = $this->request->getPost('rating_service') ?? null;

        $ticket = $this->ticketModel->find($idTiket);
        if (!$ticket) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tiket tidak ditemukan']);
        }

        // Panggil model ticket_assignees
        $assignedModel = new \App\Models\M_Tiket_Assigned();

        // CARI assignee TERAKHIR (sequence terbesar)
        $lastAssignee = $assignedModel->where('id_tiket', $idTiket)
            ->orderBy('sequence', 'DESC')
            ->first();

        if ($statusKonfirmasi === 'Closed') {
            if ($ticket['status'] !== 'Done') {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Tiket belum berstatus Done']);
            }

            if (empty($komentar) || empty($ratingService) || empty($ratingTime)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Komentar, rating service dan rating waktu wajib diisi untuk menyelesaikan tiket']);
            }

            if ($lastAssignee) {
                $assignedModel->update($lastAssignee['id'], [
                    'komentar_penyelesaian' => $komentar,
                    'rating_time' => $ratingTime,
                    'rating_service' => $ratingService,
                ]);
            }

            // Kirim email ke assigned_to (jika ada)
            if ($ticket['assigned_to']) {
                $assignedUser = $this->db->table('user')
                    ->select('email, nama')
                    ->where('id_pegawai', $ticket['assigned_to'])
                    ->get()->getRow();

                if ($assignedUser) {
                    $subject = "Tiket Telah Dikonfirmasi Selesai oleh Requestor";
                    $message = "Halo {$assignedUser->nama},\n\n"
                        . "Tiket dengan judul \"{$ticket['judul']}\" telah dikonfirmasi selesai oleh requestor.\n"
                        . "Terima kasih atas penyelesaiannya.";

                    //$this->sendEmailToRequestor($assignedUser->email, $ticket, $subject, $message);
                }
            }

            $updateData = [
                'status' => 'Closed',
                'confirm_by_requestor' => 1,
                'komentar_penyelesaian' => $komentar,
                'rating_time' => $ratingTime,
                'rating_service' => $ratingService,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        } elseif ($statusKonfirmasi === 'Open') {
            if ($lastAssignee) {
                $updateData = [
                    'komentar_penyelesaian' => $komentar,
                ];
                if (empty($lastAssignee['finished_at'])) {
                    $updateData['finished_at'] = date('Y-m-d H:i:s');
                }

                $assignedModel->update($lastAssignee['id'], $updateData);
            }

            // Kirim email kembali ke unit tujuan
            $ticketData = array_merge((array) $ticket, [
                'komentar_penyelesaian' => $komentar
            ]);

            $ticketData['judul'] = $ticket['judul']; // pastikan judul tersedia

            //$this->sendEmailToUnitTujuan($ticket, 'belum_selesai');

            $updateData = [
                'status' => 'Open',
                'assigned_to' => null,
                // 'prioritas' => null,
                'komentar_staff' => null,
                'komentar_penyelesaian' => $komentar,
                'rating_time' => null,
                'rating_service' => null,
                'confirm_by_requestor' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Status tidak valid']);
        }

        $result = $this->ticketModel->update($idTiket, $updateData);

        if (!$result) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengupdate tiket',
                'debug' => $this->ticketModel->errors()
            ]);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Konfirmasi berhasil disimpan']);
    }

    public function boardStaffView()
    {
        $db = \Config\Database::connect();

        $unitUsahaList = $db->table('unit_usaha')
            ->select('id_unit_usaha, nm_unit_usaha')
            ->orderBy('nm_unit_usaha', 'ASC')
            ->get()
            ->getResultArray();
        return view('tickets/board_staff', ['unitUsahaList' => $unitUsahaList]);
    }

    public function detail($id)
    {
        $builder = $this->db->table('tiket t');
        $builder->select([
            't.*',
            't.gambar',
            'u_assigned.nama as assigned_nama',
            'p_assigned.telpon1 as assigned_telpon1',
            'p_assigned.telpon2 as assigned_telpon2',
            'k.id_kategori',
            'k.nama_kategori',
            'sk.id_subkategori',
            'sk.nama_subkategori',
            'req.nama as requestor_nama',
            'req.email as requestor_email',
            'p_requestor.telpon1 as requestor_telpon1',
            'p_requestor.telpon2 as requestor_telpon2',
            'pp.id_unit_level',
            'ul.nm_unit_level',
            'pp.id_unit_bisnis',
            'ub.nm_unit_bisnis',
            'pp.id_unit_usaha',
            'uu.nm_unit_usaha',
            'pp.id_unit_organisasi',
            'uo.nm_unit_organisasi',
            'pp.id_unit_kerja',
            'uk.nm_unit_kerja',
            'pp.id_unit_kerja_sub',
            'uks.nm_unit_kerja_sub',
            'pp.id_unit_lokasi',
            'ulok.nm_unit_lokasi',
        ]);
        $builder->join('user u_assigned', 'u_assigned.id_pegawai = t.assigned_to', 'left');
        $builder->join('pegawai p_assigned', 'p_assigned.id_pegawai = t.assigned_to', 'left');
        $builder->join('kategori k', 't.kategori_id = k.id_kategori', 'left');
        $builder->join('sub_kategori sk', 't.subkategori_id = sk.id_subkategori', 'left');
        $builder->join('user req', 'req.id_pegawai = t.id_pegawai_requestor', 'left');
        $builder->join('pegawai p_requestor', 'p_requestor.id_pegawai = t.id_pegawai_requestor', 'left');
        $builder->join('pegawai_penempatan pp', 'pp.id_pegawai = t.id_pegawai_requestor', 'left');
        $builder->join('unit_level ul', 'pp.id_unit_level = ul.id_unit_level', 'left');
        $builder->join('unit_bisnis ub', 'pp.id_unit_bisnis = ub.id_unit_bisnis', 'left');
        $builder->join('unit_usaha uu', 'pp.id_unit_usaha = uu.id_unit_usaha', 'left');
        $builder->join('unit_organisasi uo', 'pp.id_unit_organisasi = uo.id_unit_organisasi', 'left');
        $builder->join('unit_kerja uk', 'pp.id_unit_kerja = uk.id_unit_kerja', 'left');
        $builder->join('unit_kerja_sub uks', 'pp.id_unit_kerja_sub = uks.id_unit_kerja_sub', 'left');
        $builder->join('unit_lokasi ulok', 'pp.id_unit_lokasi = ulok.id_unit_lokasi', 'left');

        $builder->where('t.id_tiket', $id);
        $ticket = $builder->get()->getRowArray();

        if (!$ticket) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tiket tidak ditemukan']);
        }

        // Ambil histori penugasan (multi petugas)
        $assignees = $this->db->table('ticket_assignees ta')
            ->select([
                'ta.sequence',
                'ta.assigned_at',
                'ta.finished_at',
                'ta.komentar_penyelesaian',
                'ta.komentar_staff',
                'ta.rating_time',
                'ta.rating_service',
                'u.nama as assigned_nama',
                'p.telpon1 as assigned_telpon1',
                'p.telpon2 as assigned_telpon2'
            ])
            ->join('user u', 'u.id_pegawai = ta.assigned_to', 'left')
            ->join('pegawai p', 'p.id_pegawai = ta.assigned_to', 'left')
            ->where('ta.id_tiket', $id)
            ->orderBy('ta.sequence', 'ASC')
            ->get()->getResultArray();

        // Tambahkan durasi pengerjaan
        foreach ($assignees as &$a) {
            $a['assigned_at'] = $a['assigned_at'] ?? null;
            $a['finished_at'] = $a['finished_at'] ?? null;

            if ($a['assigned_at'] && $a['finished_at']) {
                $start = strtotime($a['assigned_at']);
                $end = strtotime($a['finished_at']);
                $a['durasi_detik'] = $end - $start;
            } else {
                $a['durasi_detik'] = null;
            }
        }

        // Format tanggal tampil
        $createdAt = Carbon::parse($ticket['created_at'])->locale('id')->isoFormat('D MMMM YYYY, HH:mm');
        $updatedAt = Carbon::parse($ticket['updated_at'])->locale('id')->isoFormat('D MMMM YYYY, HH:mm');

        $data = [
            'id_tiket' => $ticket['id_tiket'],
            'judul' => $ticket['judul'],
            'gambar' => $ticket['gambar'],
            'deskripsi' => $ticket['deskripsi'],
            'prioritas' => $ticket['prioritas'],
            'status' => $ticket['status'],
            'requestor_nama' => $ticket['requestor_nama'] ?? '-',
            'requestor_email' => $ticket['requestor_email'] ?? '-',
            'requestor_telpon1' => $ticket['requestor_telpon1'] ?? '-',
            'requestor_telpon2' => $ticket['requestor_telpon2'] ?? '-',
            'assigned_nama' => $ticket['assigned_nama'] ?? '-',
            'assigned_telpon1' => $ticket['assigned_telpon1'] ?? '-',
            'assigned_telpon2' => $ticket['assigned_telpon2'] ?? '-',
            'id_kategori' => $ticket['id_kategori'] ?? '-',
            'kategori' => $ticket['nama_kategori'] ?? '-',
            'id_subkategori' => $ticket['id_subkategori'] ?? '-',
            'subkategori' => $ticket['nama_subkategori'] ?? '-',
            'komentar_penyelesaian' => $ticket['komentar_penyelesaian'] ?? '-',
            'komentar_staff' => $ticket['komentar_staff'] ?? '-',
            'rating_time' => $ticket['rating_time'] ?? '-',
            'rating_service' => $ticket['rating_service'] ?? '-',

            'req_penempatan' => [
                'unit_level' => $ticket['nm_unit_level'] ?? $ticket['id_unit_level'] ?? '-',
                'unit_bisnis' => $ticket['nm_unit_bisnis'] ?? $ticket['id_unit_bisnis'] ?? '-',
                'unit_usaha' => $ticket['nm_unit_usaha'] ?? $ticket['id_unit_usaha'] ?? '-',
                'unit_organisasi' => $ticket['nm_unit_organisasi'] ?? $ticket['id_unit_organisasi'] ?? '-',
                'unit_kerja' => $ticket['nm_unit_kerja'] ?? $ticket['id_unit_kerja'] ?? '-',
                'unit_kerja_sub' => $ticket['nm_unit_kerja_sub'] ?? $ticket['id_unit_kerja_sub'] ?? '-',
                'unit_lokasi' => $ticket['nm_unit_lokasi'] ?? $ticket['id_unit_lokasi'] ?? '-',
            ],

            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'assignees' => $assignees,
        ];

        return $this->response->setJSON(['status' => 'success', 'data' => $data]);
    }

    public function printpdf($id)
    {
        $builder = $this->db->table('tiket t');
        $builder->select([
            't.*',
            't.gambar',
            'u_assigned.nama as assigned_nama',
            'p_assigned.telpon1 as assigned_telpon1',
            'p_assigned.telpon2 as assigned_telpon2',
            'k.nama_kategori',
            'sk.nama_subkategori',
            'req.nama as requestor_nama',
            'req.email as requestor_email',
            'p_requestor.telpon1 as requestor_telpon1',
            'p_requestor.telpon2 as requestor_telpon2',
            'pp.id_unit_level',
            'ul.nm_unit_level',
            'pp.id_unit_bisnis',
            'ub.nm_unit_bisnis',
            'pp.id_unit_usaha',
            'uu.nm_unit_usaha',
            'pp.id_unit_organisasi',
            'uo.nm_unit_organisasi',
            'pp.id_unit_kerja',
            'uk.nm_unit_kerja',
            'pp.id_unit_kerja_sub',
            'uks.nm_unit_kerja_sub',
            'pp.id_unit_lokasi',
            'ulok.nm_unit_lokasi',
        ]);
        $builder->join('user u_assigned', 'u_assigned.id_pegawai = t.assigned_to', 'left');
        $builder->join('pegawai p_assigned', 'p_assigned.id_pegawai = t.assigned_to', 'left');
        $builder->join('kategori k', 't.kategori_id = k.id_kategori', 'left');
        $builder->join('sub_kategori sk', 't.subkategori_id = sk.id_subkategori', 'left');
        $builder->join('user req', 'req.id_pegawai = t.id_pegawai_requestor', 'left');
        $builder->join('pegawai p_requestor', 'p_requestor.id_pegawai = t.id_pegawai_requestor', 'left');
        $builder->join('pegawai_penempatan pp', 'pp.id_pegawai = t.id_pegawai_requestor', 'left');
        $builder->join('unit_level ul', 'pp.id_unit_level = ul.id_unit_level', 'left');
        $builder->join('unit_bisnis ub', 'pp.id_unit_bisnis = ub.id_unit_bisnis', 'left');
        $builder->join('unit_usaha uu', 'pp.id_unit_usaha = uu.id_unit_usaha', 'left');
        $builder->join('unit_organisasi uo', 'pp.id_unit_organisasi = uo.id_unit_organisasi', 'left');
        $builder->join('unit_kerja uk', 'pp.id_unit_kerja = uk.id_unit_kerja', 'left');
        $builder->join('unit_kerja_sub uks', 'pp.id_unit_kerja_sub = uks.id_unit_kerja_sub', 'left');
        $builder->join('unit_lokasi ulok', 'pp.id_unit_lokasi = ulok.id_unit_lokasi', 'left');

        $builder->where('t.id_tiket', $id);
        $ticket = $builder->get()->getRowArray();

        if (!$ticket) {
            // Optional: bisa return halaman error PDF
            return $this->response->setStatusCode(404)->setBody("Tiket tidak ditemukan");
        }

        // Ambil histori penugasan (assignees)
        $assignees = $this->db->table('ticket_assignees ta')
            ->select([
                'ta.sequence',
                'ta.assigned_at',
                'ta.finished_at',
                'ta.komentar_penyelesaian',
                'ta.komentar_staff',
                'ta.rating_time',
                'ta.rating_service',
                'u.nama as assigned_nama',
                'p.telpon1 as assigned_telpon1',
                'p.telpon2 as assigned_telpon2'
            ])
            ->join('user u', 'u.id_pegawai = ta.assigned_to', 'left')
            ->join('pegawai p', 'p.id_pegawai = ta.assigned_to', 'left')
            ->where('ta.id_tiket', $id)
            ->orderBy('ta.sequence', 'ASC')
            ->get()->getResultArray();

        // Format waktu
        $createdAt = \Carbon\Carbon::parse($ticket['created_at'])->locale('id')->isoFormat('D MMMM YYYY, HH:mm');
        $updatedAt = \Carbon\Carbon::parse($ticket['updated_at'])->locale('id')->isoFormat('D MMMM YYYY, HH:mm');

        // Siapkan data untuk view PDF
        $data = [
            'id_tiket' => $ticket['id_tiket'],
            'judul' => $ticket['judul'],
            'gambar' => $ticket['gambar'],
            'deskripsi' => $ticket['deskripsi'],
            'prioritas' => $ticket['prioritas'],
            'status' => $ticket['status'],
            'requestor_nama' => $ticket['requestor_nama'] ?? '-',
            'requestor_email' => $ticket['requestor_email'] ?? '-',
            'requestor_telpon1' => $ticket['requestor_telpon1'] ?? '-',
            'requestor_telpon2' => $ticket['requestor_telpon2'] ?? '-',
            'assigned_nama' => $ticket['assigned_nama'] ?? '-',
            'assigned_telpon1' => $ticket['assigned_telpon1'] ?? '-',
            'assigned_telpon2' => $ticket['assigned_telpon2'] ?? '-',
            'kategori' => $ticket['nama_kategori'] ?? '-',
            'subkategori' => $ticket['nama_subkategori'] ?? '-',
            'komentar_penyelesaian' => $ticket['komentar_penyelesaian'] ?? '-',
            'komentar_staff' => $ticket['komentar_staff'] ?? '-',
            'rating_time' => $ticket['rating_time'] ?? '-',
            'rating_service' => $ticket['rating_service'] ?? '-',
            'req_penempatan' => [
                'unit_level' => $ticket['nm_unit_level'] ?? $ticket['id_unit_level'] ?? '-',
                'unit_bisnis' => $ticket['nm_unit_bisnis'] ?? $ticket['id_unit_bisnis'] ?? '-',
                'unit_usaha' => $ticket['nm_unit_usaha'] ?? $ticket['id_unit_usaha'] ?? '-',
                'unit_organisasi' => $ticket['nm_unit_organisasi'] ?? $ticket['id_unit_organisasi'] ?? '-',
                'unit_kerja' => $ticket['nm_unit_kerja'] ?? $ticket['id_unit_kerja'] ?? '-',
                'unit_kerja_sub' => $ticket['nm_unit_kerja_sub'] ?? $ticket['id_unit_kerja_sub'] ?? '-',
                'unit_lokasi' => $ticket['nm_unit_lokasi'] ?? $ticket['id_unit_lokasi'] ?? '-',
            ],
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'assignees' => $assignees,
        ];
        $logoPath = FCPATH . 'assets/logo.png';
        if (!file_exists($logoPath)) {
            die("Gambar tidak ditemukan di: " . $logoPath);
        }


        // Render view ke HTML (app/Views/tickets/pdf_template.php)
        $html = view('tickets/pdf_ticket', ['ticket' => $data]);

        // Load library dompdf
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // (optional: preview di browser atau langsung download)
        $pdfName = 'tiket_' . $id . '.pdf';

        // Output PDF sebagai download
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $pdfName . '"')
            ->setBody($dompdf->output());
    }

    public function report()
    {
        $session = session();
        $idPegawai = $session->get('id_pegawai');
        $unitKerjaSub = session()->get('unit_kerja_sub_id');
        $monthlySummary = $this->getMonthlySummary();

        // Ambil semua unit usaha
        $unitUsahaList = $this->db->table('unit_usaha')->get()->getResultArray();

        // Ambil kategori yang ditugaskan ke sub unit kerja user
        $kategori = $this->db->table('kategori')
            ->like('penanggung_jawab', '"' . $unitKerjaSub . '"')
            ->get()
            ->getResultArray();

        $penempatan = $this->db->table('pegawai_penempatan')
            ->where('id_pegawai', $idPegawai)
            ->get()
            ->getRow();

        $namaUnitUsaha = $penempatan->nm_unit_usaha ?? '-';
        $idUnitKerja = $penempatan->id_unit_kerja ?? null;

        $kategori = $this->db->table('kategori')
            ->where('unit_kerja', $idUnitKerja)
            ->get()
            ->getResultArray();

        // Dummy summary
        $statusSummary = [
            'Open' => 10,
            'In Progress' => 7,
            'Done' => 15,
            'Closed' => 8
        ];

        return view('tickets/report', [
            'statusCounts' => [],
            'bulanLabels' => $monthlySummary['labels'],
            'jumlahTiketBulan' => $monthlySummary['data'],
            'avgTime' => $monthlySummary['totalRatingTime'],
            'avgService' => $monthlySummary['totalRatingService'],
            'statusSummary' => $statusSummary,
            'kategori' => $kategori,
            'unitUsahaList' => $unitUsahaList,
            'namaUnitUsaha' => $namaUnitUsaha,
        ]);
    }

    public function reportData()
    {
        $request = service('request');
        $session = session();

        $start = (int) ($request->getPost('start') ?? 0);
        $length = (int) ($request->getPost('length') ?? 10);
        $draw = (int) ($request->getPost('draw') ?? 1);
        $searchValue = $request->getPost('search')['value'] ?? '';

        $order = $request->getPost('order');
        $orderColumnIndex = isset($order[0]['column']) ? (int) $order[0]['column'] : 5; // default ke kolom created_at (index ke-5)
        $orderDir = isset($order[0]['dir']) ? $order[0]['dir'] : 'desc';

        // Ambil penempatan user
        $db = \Config\Database::connect();
        $idPegawai = $session->get('id_pegawai');
        $penempatan = $db->table('pegawai_penempatan')
            ->select('id_unit_kerja')
            ->where('id_pegawai', $idPegawai)
            ->get()
            ->getRow();

        if (!$penempatan) {
            return $this->response->setJSON([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        $columns = [
            'p.nama',
            'uu.nama_unit_usaha',
            't.judul',
            'k.nama_kategori',
            'sk.nama_subkategori',
            't.created_at',
            'ta.assigned_at',
            'ta.finished_at',
            't.prioritas',
            't.status',
            'petugas.nama',
        ];

        $builder = $this->db->table('tiket t');
        $builder->select('
        t.id_tiket, 
        t.judul, 
        k.nama_kategori, 
        sk.nama_subkategori, 
        t.prioritas, 
        t.status, 
        t.created_at, 
        t.confirm_by_requestor, 
        p.nama as nama_requestor,
        uu.nm_unit_usaha as nama_unit_usaha,
        petugas.nama as nama_petugas,
        ta.assigned_at as waktu_mulai, 
        ta.finished_at as waktu_selesai,
        TIMESTAMPDIFF(SECOND, ta.assigned_at, ta.finished_at) as durasi_detik,
        t.rating_time,
        t.rating_service
    ');
        $builder->join('kategori k', 't.kategori_id = k.id_kategori', 'left');
        $builder->join('sub_kategori sk', 't.subkategori_id = sk.id_subkategori', 'left');
        $builder->join('pegawai p', 't.id_pegawai_requestor = p.id_pegawai', 'left');
        $builder->join('pegawai petugas', 't.assigned_to = petugas.id_pegawai', 'left');
        $builder->join('unit_usaha uu', 't.unit_usaha_requestor = uu.id_unit_usaha', 'left');
        $builder->join("(SELECT id_tiket, MAX(sequence) as max_seq FROM ticket_assignees GROUP BY id_tiket) as max_ta", "max_ta.id_tiket = t.id_tiket", "left");
        $builder->join("ticket_assignees ta", "ta.id_tiket = t.id_tiket AND ta.sequence = max_ta.max_seq", "left");
        $builder->groupStart();

        $penempatan = $db->table('pegawai_penempatan')
            ->select('id_unit_usaha, id_unit_kerja, id_unit_kerja_sub, id_unit_level')
            ->where('id_pegawai', $idPegawai)
            ->get()
            ->getRow();

        if ($penempatan->id_unit_kerja === 'E21') {
            $builder->orGroupStart()
                ->where('t.id_unit_tujuan', 'E21')
                ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                ->groupEnd();
        }

        // Jika user dengan level A7 bisa melihat semua tiket dengan id_unit_tujuan sesuai id_unit_kerja
        if ($penempatan->id_unit_level === 'A7') {
            $builder->orWhere('t.id_unit_tujuan', value: $penempatan->id_unit_kerja);
        }

        if ($penempatan->id_unit_usaha === 'C1' && $penempatan->id_unit_kerja_sub === 'F39') {
            $builder->orWhere('t.id_unit_kerja_sub_tujuan', 'F39');
        }

        if ($penempatan->id_unit_usaha && $penempatan->id_unit_kerja) {
            $builder->orGroupStart()
                ->where('t.unit_bisnis_requestor', 'B2')
                ->where('t.id_unit_tujuan', 'E15')
                ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                ->where('t.id_unit_tujuan', $penempatan->id_unit_kerja)
                ->groupEnd();
        }

        if (
            isset($penempatan->id_unit_usaha, $penempatan->id_unit_kerja_sub) &&
            $penempatan->id_unit_usaha === 'C1' &&
            $penempatan->id_unit_kerja_sub === 'F40'
        ) {
            $builder->orGroupStart()
                ->where('t.unit_bisnis_requestor', 'B3')
                ->where('t.id_unit_tujuan', 'E15')
                ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                ->groupEnd();
        }


        if ($penempatan->id_unit_usaha === 'C1' && $penempatan->id_unit_kerja_sub === 'F38') {
            if ($penempatan->id_unit_level === 'A8') {
                // Akses khusus A8
                $builder->orGroupStart()
                    ->where('t.id_unit_tujuan', 'E15')
                    ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                    ->groupEnd();
            } else {
                // Level lain (non A8)
                $builder->orGroupStart()
                    ->where('t.id_unit_tujuan', 'E15')
                    ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                    ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                    ->groupEnd();
            }
        }


        if ($penempatan->id_unit_kerja === 'E21') {
            $builder->orGroupStart()
                ->where('t.id_unit_tujuan', 'E21')
                ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                ->where('t.id_unit_tujuan', $penempatan->id_unit_kerja)
                ->groupEnd();
        }

        // Tiket closed dan pernah ditugaskan
        $builder->orGroupStart()
            ->where('t.status', 'Closed')
            ->where('t.assigned_to', $idPegawai)
            ->groupEnd();

        $builder->groupEnd(); // akhir group


        $baseBuilder = clone $builder;

        // Total data sebelum filter
        $totalData = $baseBuilder->countAllResults();

        // Tambahkan search jika ada
        if (!empty($searchValue)) {
            $builder->groupStart()
                ->like('p.nama', $searchValue)
                ->orLike('uu.nm_unit_usaha', $searchValue)
                ->orLike('t.judul', $searchValue)
                ->orLike('k.nama_kategori', $searchValue)
                ->orLike('sk.nama_subkategori', $searchValue)
                ->orLike('t.status', $searchValue)
                ->orLike('petugas.nama', $searchValue)
                ->orLike('t.created_at', $searchValue)
                ->orLike('ta.assigned_at', $searchValue)
                ->orLike('ta.finished_at', $searchValue)
                ->groupEnd();
        }

        // Hitung total setelah filter
        $filteredBuilder = clone $builder;
        $totalFiltered = $filteredBuilder->countAllResults();

        // Sorting kolom (sesuaikan dengan urutan columns di DataTables frontend)
        if (isset($columns[$orderColumnIndex])) {
            $builder->orderBy($columns[$orderColumnIndex], $orderDir);
        } else {
            $builder->orderBy('t.created_at', 'DESC'); // default: terbaru dulu
        }

        // Ambil data sesuai paginasi
        $data = $builder->limit($length, $start)->get()->getResultArray();

        foreach ($data as &$row) {
            $row['waktu_mulai'] = $row['waktu_mulai'] ? date('Y-m-d H:i', strtotime($row['waktu_mulai'])) : '-';
            $row['waktu_selesai'] = $row['waktu_selesai'] ? date('Y-m-d H:i', strtotime($row['waktu_selesai'])) : '-';

            $dur = (int) $row['durasi_detik'];
            if ($dur > 0) {
                $hours = floor($dur / 3600);
                $minutes = floor(($dur % 3600) / 60);
                $row['durasi'] = ($hours > 0 ? "{$hours} jam " : '') . "{$minutes} menit";
            } else {
                $row['durasi'] = '-';
            }
        }

        return $this->response->setJSON([
            "draw" => $draw,
            "recordsTotal" => $totalData,
            "recordsFiltered" => $totalFiltered,
            "data" => $data,
        ]);
    }

    //jumlah tiket masuk perbulan dalam setahun
    private function getMonthlySummary()
    {
        $db = \Config\Database::connect();
        $session = session();
        $idPegawai = $session->get('id_pegawai');

        $penempatan = $db->table('pegawai_penempatan')
            ->select('id_unit_usaha, id_unit_kerja, id_unit_kerja_sub, id_unit_level')
            ->where('id_pegawai', $idPegawai)
            ->get()
            ->getRow();

        if (!$penempatan) {
            return [
                'labels' => [],
                'data' => [],
                'totalRatingTime' => 0,
                'totalRatingService' => 0,
            ];
        }

        $labels = [];
        $data = array_fill(0, 12, 0);

        // Tiket Masuk per Bulan
        $builder = $db->table('tiket t');
        $builder->select('MONTH(created_at) as bulan, COUNT(*) as total');
        $builder->where('YEAR(created_at)', date('Y'));
        $this->applyTiketPenempatanFilter($builder, $penempatan, $idPegawai);
        $builder->groupBy('MONTH(created_at)');
        $builder->orderBy('MONTH(created_at)');
        $result = $builder->get()->getResultArray();

        foreach ($result as $row) {
            $index = (int) $row['bulan'] - 1;
            $data[$index] = $row['total'];
        }

        // Rata-rata Rating (waktu & layanan), hanya dari tiket yang boleh dilihat
        $ratingQuery = $db->table('tiket t')
            ->select('AVG(rating_time) as avg_rating_time, AVG(rating_service) as avg_rating_service');
        $this->applyTiketPenempatanFilter($ratingQuery, $penempatan, $idPegawai);
        $ratingRow = $ratingQuery->get()->getRow();

        return [
            'labels' => array_map(fn($i) => date('F', mktime(0, 0, 0, $i, 1)), range(1, 12)),
            'data' => $data,
            'totalRatingTime' => round($ratingRow->avg_rating_time ?? 0, 2),
            'totalRatingService' => round($ratingRow->avg_rating_service ?? 0, 2),
        ];
    }



    //tiket masuk berdasarkan status
    public function getStatusSummaryByUnit()
    {
        $session = session();
        $idPegawai = $session->get('id_pegawai');

        $db = \Config\Database::connect();
        $penempatan = $db->table('pegawai_penempatan')
            ->select('id_unit_usaha, id_unit_kerja, id_unit_kerja_sub, id_unit_level')
            ->where('id_pegawai', $idPegawai)
            ->get()
            ->getRow();

        if (!$penempatan) {
            return $this->response->setJSON([
                'Open' => 0,
                'In Progress' => 0,
                'Done' => 0,
                'Closed' => 0
            ]);
        }

        $builder = $db->table('tiket t')
            ->select('status, COUNT(*) as total')
            ->where('MONTH(created_at)', date('m'))
            ->where('YEAR(created_at)', date('Y'));

        $this->applyTiketPenempatanFilter($builder, $penempatan, $idPegawai);
        $builder->groupBy('status');

        $result = $builder->get()->getResult();

        $statusSummary = [
            'Open' => 0,
            'In Progress' => 0,
            'Done' => 0,
            'Closed' => 0
        ];

        foreach ($result as $row) {
            if (isset($statusSummary[$row->status])) {
                $statusSummary[$row->status] = (int) $row->total;
            }
        }

        return $this->response->setJSON($statusSummary);
    }

    //tiket masuk berdasarkan kategori
    public function getCategorySummaryByMonth()
    {
        $session = session();
        $idPegawai = $session->get('id_pegawai');
        $db = \Config\Database::connect();

        $penempatan = $db->table('pegawai_penempatan')
            ->select('id_unit_usaha, id_unit_kerja, id_unit_kerja_sub, id_unit_level')
            ->where('id_pegawai', $idPegawai)
            ->get()
            ->getRow();

        if (!$penempatan) {
            return $this->response->setJSON([]);
        }

        $builder = $db->table('tiket t')
            ->select('k.nama_kategori, sk.nama_subkategori, COUNT(*) as total')
            ->join('kategori k', 't.kategori_id = k.id_kategori', 'left')
            ->join('sub_kategori sk', 't.subkategori_id = sk.id_subkategori', 'left')
            ->where('MONTH(t.created_at)', date('m'))
            ->where('YEAR(t.created_at)', date('Y'));

        $this->applyTiketPenempatanFilter($builder, $penempatan, $idPegawai);

        $builder->groupBy(['t.kategori_id', 't.subkategori_id']);
        $builder->orderBy('k.nama_kategori, sk.nama_subkategori');
        $results = $builder->get()->getResult();

        // Format data untuk grouped bar chart
        $categories = [];
        $subCategories = [];
        $map = [];

        foreach ($results as $row) {
            $kategori = $row->nama_kategori ?? 'Tanpa Kategori';
            $subKategori = $row->nama_subkategori ?? 'Tanpa Subkategori';

            if (!in_array($kategori, $categories)) {
                $categories[] = $kategori;
            }

            if (!in_array($subKategori, $subCategories)) {
                $subCategories[] = $subKategori;
            }

            $map[$kategori][$subKategori] = (int) $row->total;
        }

        // Sort categories dan subcategories untuk konsistensi
        sort($categories);
        sort($subCategories);

        // Jika tidak ada data, return empty
        if (empty($categories) || empty($subCategories)) {
            return $this->response->setJSON([
                'labels' => [],
                'datasets' => []
            ]);
        }


        // Susun dataset untuk grouped bar chart
        $datasets = [];
        $colors = [
            '#3B82F6', // Blue
            '#10B981', // Green
            '#F59E0B', // Yellow
            '#EF4444', // Red
            '#8B5CF6', // Purple
            '#06B6D4', // Cyan
            '#F97316', // Orange
            '#84CC16', // Lime
            '#EC4899', // Pink
            '#6366F1', // Indigo
        ];

        foreach ($subCategories as $index => $sub) {
            $data = [];
            foreach ($categories as $kat) {
                $data[] = $map[$kat][$sub] ?? 0;
            }
            $colorIndex = $index % count($colors);
            $datasets[] = [
                'label' => $sub,
                'data' => $data,
                'backgroundColor' => $colors[$colorIndex],
                'borderColor' => $colors[$colorIndex],
                'borderWidth' => 1,
                'borderRadius' => 4,
                'hoverBackgroundColor' => $colors[$colorIndex],
                'fill' => false
            ];
        }

        return $this->response->setJSON([
            'labels' => $categories,
            'datasets' => $datasets
        ]);
    }


    private function applyTiketPenempatanFilter($builder, $penempatan, $idPegawai)
    {
        $builder->groupStart();

        if ($penempatan->id_unit_level === 'A7') {
            $builder->orWhere('t.id_unit_tujuan', $penempatan->id_unit_kerja);
        }

        if ($penempatan->id_unit_usaha === 'C1' && $penempatan->id_unit_kerja_sub === 'F39') {
            $builder->orWhere('t.id_unit_kerja_sub_tujuan', 'F39');
        }

        if ($penempatan->id_unit_usaha && $penempatan->id_unit_kerja) {
            $builder->orGroupStart()
                ->where('t.unit_bisnis_requestor', 'B2')
                ->where('t.id_unit_tujuan', 'E15')
                ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                ->where('t.id_unit_tujuan', $penempatan->id_unit_kerja)
                ->groupEnd();
        }

        if ($penempatan->id_unit_usaha === 'C1' && $penempatan->id_unit_kerja_sub === 'F40') {
            $builder->orGroupStart()
                ->where('t.unit_bisnis_requestor', 'B3')
                ->where('t.id_unit_tujuan', 'E15')
                ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                ->groupEnd();
        }

        if ($penempatan->id_unit_usaha === 'C1' && $penempatan->id_unit_kerja_sub === 'F38') {
            if ($penempatan->id_unit_level === 'A8') {
                $builder->orGroupStart()
                    ->where('t.id_unit_tujuan', 'E15')
                    ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                    ->groupEnd();
            } else {
                $builder->orGroupStart()
                    ->where('t.id_unit_tujuan', 'E15')
                    ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                    ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                    ->groupEnd();
            }
        }

        if ($penempatan->id_unit_kerja === 'E21') {
            $builder->orGroupStart()
                ->where('t.id_unit_tujuan', 'E21')
                ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                ->groupEnd();
        }

        // Tiket Closed yang pernah ditangani user
        $builder->orGroupStart()
            ->where('t.status', 'Closed')
            ->where('t.assigned_to', $idPegawai)
            ->groupEnd();

        $builder->groupEnd(); // akhir semua filter
    }


    public function printReportPdf()
    {
        $request = service('request');
        $status = $request->getGet('status') ?? [];
        $kategoriId = $request->getGet('kategori') ?? [];
        $tanggalMulai = $request->getGet('tanggal_mulai');
        $tanggalSelesai = $request->getGet('tanggal_selesai');
        $unitUsaha = $request->getGet('unit_usaha') ?? [];

        $idPegawai = session()->get('id_pegawai');

        $db = \Config\Database::connect();

        $penempatan = $db->table('pegawai_penempatan')
            ->select('id_unit_usaha, id_unit_kerja, id_unit_kerja_sub, id_unit_level')
            ->where('id_pegawai', $idPegawai)
            ->get()
            ->getRow();

        if (!$penempatan) {
            return $this->renderPdf([], 'laporan_tiket_kosong_');
        }

        $builder = $db->table('tiket t');
        $builder->select('
        t.id_tiket,
        t.judul,
        t.status,
        t.created_at,
        k.nama_kategori,
        sk.nama_subkategori,
        p.nama as nama_requestor,
        uu.nm_unit_usaha,
        petugas.nama as nama_petugas,
        ta.assigned_at as waktu_mulai,
        ta.finished_at as waktu_selesai,
        TIMESTAMPDIFF(SECOND, ta.assigned_at, ta.finished_at) as durasi_detik,
        t.rating_time,
        t.rating_service
    ');
        $builder->join('kategori k', 'k.id_kategori = t.kategori_id', 'left');
        $builder->join('sub_kategori sk', 'sk.id_subkategori = t.subkategori_id', 'left');
        $builder->join('pegawai p', 't.id_pegawai_requestor = p.id_pegawai', 'left');
        $builder->join('unit_usaha uu', 't.unit_usaha_requestor = uu.id_unit_usaha', 'left');
        $builder->join('pegawai petugas', 't.assigned_to = petugas.id_pegawai', 'left');
        $builder->join('(SELECT id_tiket, MAX(sequence) as max_seq FROM ticket_assignees GROUP BY id_tiket) as max_ta', 'max_ta.id_tiket = t.id_tiket', 'left');
        $builder->join('ticket_assignees ta', 'ta.id_tiket = t.id_tiket AND ta.sequence = max_ta.max_seq', 'left');

        // === Logika akses user ===
        $builder->groupStart();

        if ($penempatan->id_unit_kerja === 'E21') {
            $builder->orGroupStart()
                ->where('t.id_unit_tujuan', 'E21')
                ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                ->groupEnd();
        }

        if ($penempatan->id_unit_level === 'A7') {
            $builder->orWhere('t.id_unit_tujuan', $penempatan->id_unit_kerja);
        }

        if ($penempatan->id_unit_kerja_sub === 'F39') {
            $builder->orWhere('t.id_unit_kerja_sub_tujuan', 'F39');
        }

        if ($penempatan->id_unit_usaha && $penempatan->id_unit_kerja) {
            $builder->orGroupStart()
                ->where('t.unit_bisnis_requestor', 'B2')
                ->where('t.id_unit_tujuan', 'E15')
                ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                ->where('t.id_unit_tujuan', $penempatan->id_unit_kerja)
                ->groupEnd();
        }

        if (
            $penempatan->id_unit_usaha === 'C1' &&
            $penempatan->id_unit_kerja_sub === 'F40'
        ) {
            $builder->orGroupStart()
                ->where('t.unit_bisnis_requestor', 'B3')
                ->where('t.id_unit_tujuan', 'E15')
                ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                ->groupEnd();
        }

        if ($penempatan->id_unit_usaha === 'C1' && $penempatan->id_unit_kerja_sub === 'F38') {
            if ($penempatan->id_unit_level === 'A8') {
                // Akses khusus A8
                $builder->orGroupStart()
                    ->where('t.id_unit_tujuan', 'E15')
                    ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                    ->groupEnd();
            } else {
                // Level lain (non A8)
                $builder->orGroupStart()
                    ->where('t.id_unit_tujuan', 'E15')
                    ->where('t.id_unit_kerja_sub_tujuan !=', 'F39')
                    ->where('t.unit_usaha_requestor', $penempatan->id_unit_usaha)
                    ->groupEnd();
            }
        }

        if (!empty($status)) {
            $builder->whereIn('t.status', $status);
        }

        if (!empty($kategoriId)) {
            $builder->whereIn('t.kategori_id', $kategoriId);
        }

        if (!empty($unitUsaha)) {
            $builder->whereIn('t.unit_usaha_requestor', $unitUsaha);
        }

        // Tiket Closed yang pernah ditugaskan ke user
        $builder->orGroupStart()
            ->where('t.status', 'Closed')
            ->where('t.assigned_to', $idPegawai)
            ->groupEnd();

        $builder->groupEnd();

        // === Filter tambahan dari form ===
        if (!empty($status)) {
            $builder->whereIn('t.status', $status);
        }

        if (!empty($kategoriId)) {
            $builder->whereIn('t.kategori_id', $kategoriId);
        }

        if (!empty($unitUsaha)) {
            $builder->whereIn('t.unit_usaha_requestor', $unitUsaha);
        }

        if ($tanggalMulai) {
            $builder->where('t.created_at >=', $tanggalMulai . ' 00:00:00');
        }

        if ($tanggalSelesai) {
            $builder->where('t.created_at <=', $tanggalSelesai . ' 23:59:59');
        }

        $tickets = $builder->get()->getResultArray();

        // Format durasi sebelum kirim ke PDF
        foreach ($tickets as &$row) {
            $dur = (int) $row['durasi_detik'];
            if ($dur > 0) {
                $hours = floor($dur / 3600);
                $minutes = floor(($dur % 3600) / 60);
                $row['durasi'] = ($hours > 0 ? "{$hours} jam " : '') . "{$minutes} menit";
            } else {
                $row['durasi'] = '-';
            }

            $row['waktu_mulai'] = $row['waktu_mulai'] ? date('Y-m-d H:i', strtotime($row['waktu_mulai'])) : '-';
            $row['waktu_selesai'] = $row['waktu_selesai'] ? date('Y-m-d H:i', strtotime($row['waktu_selesai'])) : '-';
        }

        return $this->renderPdf($tickets, 'laporan_tiket_', 'report');

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
