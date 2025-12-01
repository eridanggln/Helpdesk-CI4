<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class EmailSender extends Controller
{
    public function sendTestEmail()
    {
        $email = \Config\Services::email();

        $email->setTo('eridanainggolan9@gmail.com');
        $email->setFrom('erida21ti@mahasiswa.pcr.ac.id', 'Help Desk System');
        $email->setSubject('Perubahan Status Tiket');
        $email->setMessage('Pegawai Yudistira melakukan pengalihan tiket dengan tiket id 10.    ');

        if (! $email->send()) {
            echo $email->printDebugger(['headers']);
        } else {
            echo "Email berhasil dikirim!";
        }
    }
}
