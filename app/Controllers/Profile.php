<?php

namespace App\Controllers;
use CodeIgniter\Controller;

class Profile extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        return view('profile/index');
    }

    public function changePassword()
    {
        $session = session();
        $userId = $session->get('id_pegawai');
        $user = $this->db->table('user')->where('id_pegawai', $userId)->get()->getRow();

        if (!$user) {
            return redirect()->back()->with('error', 'User tidak ditemukan.');
        }

        $oldPassword = $this->request->getPost('old_password');
        $newPassword = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Password baru dan konfirmasi tidak cocok.');
        }

        if (!password_verify($oldPassword, $user->password)) {
            return redirect()->back()->with('error', 'Password lama salah.');
        }

        // Update password
        $this->db->table('user')->where('id_pegawai', $userId)->update([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        return redirect()->back()->with('success', 'Password berhasil diubah.');
    }

}
