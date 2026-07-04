<?php namespace App\Controllers;

use App\Controllers\BaseController;

class Login extends BaseController
{
    public function index()
    {
        // Jika sudah login, langsung ke admin
        if (session()->get('isLoggedIn')) return redirect()->to('/admin');
        return view('login_view');
    }

    public function auth()
    {
        $session = session();
        
        // --- 1. VERIFIKASI RECAPTCHA ---
        $recaptchaResponse = $this->request->getPost('g-recaptcha-response');
        
        // PASTE SECRET KEY BARU DI SINI
        $secretKey = '6LdXpB8sAAAAABKO-hb87XEig_Y_o7o9IETNl6n0'; 

        $client = \Config\Services::curlrequest();
        
        try {
            $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret'   => $secretKey,
                    'response' => $recaptchaResponse,
                    'remoteip' => $this->request->getIPAddress(),
                ]
            ]);
            
            $result = json_decode($response->getBody());

            // Jika Gagal (Bot atau Key Salah)
            if (!$result->success) {
                 return redirect()->to('/login')->withInput()->with('error', 'Verifikasi Captcha Gagal! Mohon centang "Saya bukan robot".');
            }

        } catch (\Exception $e) {
            return redirect()->to('/login')->withInput()->with('error', 'Gagal koneksi ke Google.');
        }

        // --- 2. CEK LOGIN USERNAME & PASSWORD ---
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $db = \Config\Database::connect();
        $user = $db->table('users')->where('username', $username)->get()->getRowArray();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $session->set([
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'isLoggedIn' => TRUE
                ]);
                return redirect()->to('/admin');
            }
        }
        
        return redirect()->to('/login')->withInput()->with('error', 'Username atau Password Salah!');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}