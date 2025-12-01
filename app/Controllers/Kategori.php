<?php

namespace App\Controllers;

use App\Models\M_Kategori;

use CodeIgniter\Controller;

class Kategori extends Controller
{
    protected $kategoriModel;
    protected $session;

    public function __construct()
    {
        $this->kategoriModel = new M_Kategori();
        $this->session = session();
    }

    public function index()
    {
        $unitKerja = $this->session->get('unit_kerja_id');
        // Ambil hanya kategori yang dibuat oleh unit kerja user
        $kategori = $this->kategoriModel
            ->where('unit_kerja', $unitKerja)
            ->findAll();

        $db = \Config\Database::connect();
        $unitKerjaSubList = $db->table('unit_kerja_sub')
            ->select('id_unit_kerja_sub, nm_unit_kerja_sub')
            ->get()->getResultArray();

        // Buat mapping ID → Nama
        $mapUnitKerjaSub = [];
        foreach ($unitKerjaSubList as $uks) {
            $mapUnitKerjaSub[$uks['id_unit_kerja_sub']] = $uks['nm_unit_kerja_sub'];
        }

        $data['kategori'] = $kategori;
        $data['mapUnitKerjaSub'] = $mapUnitKerjaSub;

        return view('kategori/index', $data);
    }

    public function store()
    {
        $session = session();
        $idKategori = $this->kategoriModel->generateIdKategori();

        $unitKerja = $session->get('unit_kerja_id'); 

        // Default ambil dari input form
        $penanggungJawab = $this->request->getPost('penanggung_jawab');

        // Jika unit kerja adalah E21, override dengan F46
        if ($unitKerja === 'E21') {
            $penanggungJawab = 'F45';
        }

        $data = [
            'id_kategori' => $idKategori,
            'nama_kategori' => $this->request->getPost('nama_kategori'),
            'penanggung_jawab' => json_encode([$penanggungJawab]),
            'unit_kerja' => $unitKerja,
        ];

        // Cek apakah kategori sudah ada
        $existing = $this->kategoriModel
            ->where('nama_kategori', $data['nama_kategori'])
            ->where('unit_kerja', $data['unit_kerja'])
            ->countAllResults();

        if ($existing > 0) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Kategori sudah ada.'
            ]);
        }

        $this->kategoriModel->insert($data);

        return $this->response->setJSON(['status' => 'success']);
    }



    public function create()
    {
        $unitKerja = session()->get('unit_kerja_id');

        // Mapping penanggung jawab berdasarkan unit kerja
        $unitPenanggungJawabMap = [
            'E15' => [
                'F38' => 'IT Operation & Infrastructure',
                'F39' => 'IT Solution & Development',
            ],
            // Tambah unit kerja lainnya di sini jika dibutuhkan
        ];

        $penanggungJawabOptions = $unitPenanggungJawabMap[$unitKerja] ?? [];

        return view('kategori/create', [
            'penanggungJawabOptions' => $penanggungJawabOptions,
        ]);
    }

    public function edit($id = null)
    {
        $kategori = $this->kategoriModel->find($id);
        if (!$kategori) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Kategori tidak ditemukan'
            ])->setStatusCode(404);
        }

        $unitKerja = $kategori['unit_kerja'];

        $unitPenanggungJawabMap = [
            'E15' => [
                'F38' => 'IT Operation & Infrastructure',
                'F39' => 'IT Solution & Development',
            ],
            'E21' => [
                'F45' => 'General Affair',
            ],
        ];

        $penanggungOptions = $unitPenanggungJawabMap[$unitKerja] ?? [];

        return $this->response->setJSON([
            'status' => 'success',
            'id_kategori' => $kategori['id_kategori'],
            'nama_kategori' => $kategori['nama_kategori'],
            'penanggung_jawab' => $kategori['penanggung_jawab'],
            'penanggung_jawab_options' => $penanggungOptions,
        ]);
    }


    public function update($id = null)
    {
        $kategori = $this->kategoriModel->find($id);
        if (!$kategori) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Kategori tidak ditemukan'
            ])->setStatusCode(404);
        }

        $validation = \Config\Services::validation();
        $rules = ['nama_kategori' => 'required|min_length[3]|max_length[100]'];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'errors' => $validation->getErrors(),
            ])->setStatusCode(422);
        }

        $updateData = [
            'nama_kategori' => $this->request->getPost('nama_kategori'),
        ];

        $unitKerja = $kategori['unit_kerja'];

        // Hanya update penanggung_jawab jika E15 (karena hanya E15 yang bisa edit)
        if ($unitKerja === 'E15') {
            $updateData['penanggung_jawab'] = json_encode($this->request->getPost('penanggung_jawab') ?? []);
        }

        $this->kategoriModel->update($id, $updateData);

        $unitPenanggungJawabMap = [
            'E15' => [
                'F38' => 'IT Operation & Infrastructure',
                'F39' => 'IT Solution & Development',
            ],
            'E21' => [
                'F45' => 'General Affair',
            ],
        ];

        return $this->response->setJSON([
            'status' => 'success',
            'id_kategori' => $id,
            'nama_kategori' => $updateData['nama_kategori'],
            'penanggung_jawab' => $updateData['penanggung_jawab'] ?? $kategori['penanggung_jawab'],
            'penanggung_jawab_options' => $unitPenanggungJawabMap[$unitKerja] ?? []
        ]);
    }


    public function delete($id)
    {
        $kategori = $this->kategoriModel->find($id);
        if (!$kategori) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kategori tidak ditemukan']);
        }

        if ($kategori['unit_kerja'] !== $this->session->get('unit_kerja_id')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak']);
        }

        $this->kategoriModel->delete($id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Kategori berhasil dihapus.']);
    }



}
