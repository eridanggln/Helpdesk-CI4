<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use Config\Database;

class Auth extends Controller
{
    public function login()
    {
        $input = $this->request->getJSON(true);
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;
        $session = session();

        $model = new UserModel();
        $user = $model->where('email', $email)->first();

        if (!$user) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Email tidak ditemukan']);
        }
        if ($user['is_active'] != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User belum aktif']);
        }
        if (!password_verify($password, $user['password'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Password salah']);
        }

        // Ambil data penempatan
        $db = Database::connect();
        $builder = $db->table('pegawai_penempatan as pp');
        $builder->select('uu.id_unit_usaha, uu.nm_unit_usaha, uk.id_unit_kerja, uk.nm_unit_kerja, ul.id_unit_level, ul.nm_unit_level, uks.id_unit_kerja_sub, uks.nm_unit_kerja_sub, ulk.id_unit_lokasi, ulk.nm_unit_lokasi');
        $builder->join('unit_usaha uu', 'pp.id_unit_usaha = uu.id_unit_usaha', 'left');
        $builder->join('unit_kerja uk', 'pp.id_unit_kerja = uk.id_unit_kerja', 'left');
        $builder->join('unit_level ul', 'pp.id_unit_level = ul.id_unit_level', 'left');
        $builder->join('unit_kerja_sub uks', 'pp.id_unit_kerja_sub = uks.id_unit_kerja_sub', 'left');
        $builder->join('unit_lokasi ulk', 'pp.id_unit_lokasi = ulk.id_unit_lokasi', 'left');
        $builder->where('pp.id_pegawai', $user['id_pegawai']);
        $penempatan = $builder->get()->getRowArray();

        $unitKerjaSubId = $penempatan['id_unit_kerja_sub'] ?? '';

        // Simpan ke session
        $session->set([
            'user_id' => $user['user_id'],
            'id_pegawai' => $user['id_pegawai'],
            'nama' => $user['nama'],
            'email' => $user['email'],
            'role_id' => $user['role_id'],
            'unit_usaha_id' => $penempatan['id_unit_usaha'] ?? '',
            'unit_usaha' => $penempatan['nm_unit_usaha'] ?? '',
            'unit_kerja' => $penempatan['nm_unit_kerja'] ?? '',
            'unit_kerja_id' => $penempatan['id_unit_kerja'] ?? '',
            'unit_level_id' => $penempatan['id_unit_level'] ?? '',
            'unit_level_name' => $penempatan['nm_unit_level'] ?? '',
            'id_unit_kerja_sub' => $unitKerjaSubId,
            'unit_kerja_sub_id' => $unitKerjaSubId,
            'unit_kerja_sub_name' => $penempatan['nm_unit_kerja_sub'] ?? '',
            'unit_lokasi_name' => $penempatan['nm_unit_lokasi'] ?? '',
            'logged_in' => true,
        ]);

        // Tentukan halaman redirect
        $dashboardSubIDs = ['F38', 'F39', 'F40', 'F45'];

        $redirect = in_array($unitKerjaSubId, $dashboardSubIDs)
            ? '/dashboard'
            : '/tickets';

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Login berhasil',
            'redirect' => $redirect
        ]);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/');
    }
}
