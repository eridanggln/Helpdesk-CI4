<?php

namespace App\Controllers;

use App\Models\M_Kategori;
use App\Models\M_SubKategori;
use CodeIgniter\Controller;

class SubKategori extends Controller
{
    protected $subKategoriModel;
    protected $kategoriModel;
    protected $session;

    public function __construct()
    {
        $this->subKategoriModel = new M_SubKategori();
        $this->kategoriModel = new M_Kategori();
        $this->session = session();
    }

    // Index list subkategori
    public function index()
    {
        $unitKerja = $this->session->get('unit_kerja_id'); // pastikan nama session benar

        $builder = $this->subKategoriModel->builder();
        $builder->select('sub_kategori.*, kategori.nama_kategori, kategori.unit_kerja');
        $builder->join('kategori', 'sub_kategori.id_kategori = kategori.id_kategori');

        if ($unitKerja) {
            $builder->where('kategori.unit_kerja', $unitKerja); // filter berdasarkan unit_kerja user
        }

        $data['subkategori'] = $builder->get()->getResultArray();

        return view('subkategori/index', $data);
    }

    public function create()
    {
        $unitUsaha = $this->session->get('unit_usaha_id');
        $unitKerja = $this->session->get('unit_kerja_id');

        // Ambil kategori sesuai unit_kerja user (bukan unit usaha)
        $kategoriModel = new M_Kategori();
        $data['kategori'] = $kategoriModel
            ->where('unit_kerja', $unitKerja) // ini filter utama
            ->findAll();

        return view('subkategori/create', $data);
    }


    public function store()
    {
        $validation = \Config\Services::validation();

        $rules = [
            'id_kategori' => 'required',
            'nama_subkategori' => 'required|min_length[2]|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'errors' => $validation->getErrors(),
            ]);
        }

        $id_kategori = $this->request->getPost('id_kategori');
        $nama_subkategori = trim($this->request->getPost('nama_subkategori'));

        // 🔍 Cek apakah subkategori sudah ada (case-insensitive)
        $existing = $this->subKategoriModel
            ->where('id_kategori', $id_kategori)
            ->where('LOWER(nama_subkategori)', strtolower($nama_subkategori))
            ->first();

        if ($existing) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Sub Kategori sudah ada.'
            ]);
        }

        $data = [
            'id_subkategori' => $this->subKategoriModel->generateIdSubKategori(),
            'id_kategori' => $id_kategori,
            'nama_subkategori' => $nama_subkategori,
        ];

        $this->subKategoriModel->insert($data);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Sub Kategori berhasil ditambahkan.'
        ]);
    }

    // Ambil data subkategori untuk modal edit (ajax)
    public function edit($id)
    {
        $subkategori = $this->subKategoriModel->find($id);
        if (!$subkategori) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'SubKategori tidak ditemukan']);
        }

        // Cek akses
        $kategori = $this->kategoriModel->find($subkategori['id_kategori']);
        if (!$kategori || $kategori['unit_kerja'] !== $this->session->get('unit_kerja_id')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak']);
        }

        // Ambil semua kategori dari unit kerja yang sama
        $kategoriList = $this->kategoriModel
            ->where('unit_kerja', $this->session->get('unit_kerja_id'))
            ->findAll();

        $kategoriOptions = [];
        foreach ($kategoriList as $kat) {
            $kategoriOptions[$kat['id_kategori']] = $kat['nama_kategori'];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $subkategori,
            'kategori_options' => $kategoriOptions,
        ]);
    }


    // Update subkategori via ajax
    public function update($id)
    {
        $subkategori = $this->subKategoriModel->find($id);
        if (!$subkategori) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'SubKategori tidak ditemukan']);
        }

        $kategori = $this->kategoriModel->find($subkategori['id_kategori']);
        if (!$kategori || $kategori['unit_kerja'] !== $this->session->get('unit_kerja_id')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak']);
        }

        $validation = \Config\Services::validation();
        $rules = [
            'nama_subkategori' => 'required|min_length[3]|max_length[100]',
            'id_kategori' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'errors' => $validation->getErrors(),
            ]);
        }

        $this->subKategoriModel->update($id, [
            'nama_subkategori' => $this->request->getPost('nama_subkategori'),
            'id_kategori' => $this->request->getPost('id_kategori'),
        ]);


        return $this->response->setJSON(['status' => 'success', 'message' => 'SubKategori berhasil diupdate.']);
    }

    // Delete subkategori via ajax
    public function delete($id)
    {
        $subkategori = $this->subKategoriModel->find($id);
        if (!$subkategori) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'SubKategori tidak ditemukan']);
        }

        // Ambil kategori terkait
        $kategori = (new M_Kategori())->find($subkategori['id_kategori']);
        if (!$kategori) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kategori terkait tidak ditemukan']);
        }

        if ($kategori['unit_kerja'] !== $this->session->get('unit_kerja_id')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak']);
        }

        $this->subKategoriModel->delete($id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Sub Kategori berhasil dihapus.']);
    }
}
