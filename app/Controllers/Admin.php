<?php

namespace App\Controllers;

use App\Models\FaqModel;

class Admin extends BaseController
{
    protected $faqModel;

    public function __construct()
    {
        $this->faqModel = new FaqModel();
    }

    // 1. HALAMAN UTAMA ADMIN (List Data)
    public function index()
    {
        $data = [
            'faqs' => $this->faqModel->orderBy('id', 'DESC')->findAll()
        ];
        return view('admin/index', $data);
    }

    // 2. HALAMAN TAMBAH DATA
    public function create()
    {
        return view('admin/form', ['title' => 'Tambah Data FAQ', 'data' => null]);
    }

    // 3. PROSES SIMPAN DATA BARU (DITAMBAH TRIGGER PYTHON)
    public function store()
    {
        $this->faqModel->save([
            'parent_id'     => $this->request->getPost('parent_id') ?: null,
            'key_name'      => $this->request->getPost('key_name'),
            'question_text' => $this->request->getPost('question_text'),
            'answer_text'   => $this->request->getPost('answer_text'),
        ]);

        // --- TRIGGER UPDATE AI PYTHON ---
        $this->refreshPythonAI(); 

        return redirect()->to('/admin')->with('success', 'Data disimpan & Otak AI diperbarui!');
    }

    // 4. HALAMAN EDIT DATA
    public function edit($id)
    {
        $faq = $this->faqModel->find($id);
        if (!$faq) return redirect()->to('/admin');

        return view('admin/form', ['title' => 'Edit Data FAQ', 'data' => $faq]);
    }

    // 5. PROSES UPDATE DATA (DITAMBAH TRIGGER PYTHON)
    public function update($id)
    {
        $this->faqModel->update($id, [
            'parent_id'     => $this->request->getPost('parent_id') ?: null,
            'key_name'      => $this->request->getPost('key_name'),
            'question_text' => $this->request->getPost('question_text'),
            'answer_text'   => $this->request->getPost('answer_text'),
        ]);

        // --- TRIGGER UPDATE AI PYTHON ---
        $this->refreshPythonAI();

        return redirect()->to('/admin')->with('success', 'Data diupdate & Otak AI diperbarui!');
    }

    // 6. PROSES HAPUS DATA (DITAMBAH TRIGGER PYTHON)
    public function delete($id)
    {
        $this->faqModel->delete($id);

        // --- TRIGGER UPDATE AI PYTHON ---
        $this->refreshPythonAI();

        return redirect()->to('/admin')->with('success', 'Data dihapus & Otak AI diperbarui!');
    }

    // ==========================================================
    // FUNGSI RAHASIA: MENELPON PYTHON AGAR BACA ULANG DATABASE
    // ==========================================================
    private function refreshPythonAI()
    {
        try {
            // Menembak endpoint /refresh di Python (Port 5000)
            $ch = curl_init("http://127.0.0.1:5000/refresh");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Jangan tunggu lama-lama (maks 2 detik)
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            // Kalau Python mati, biarkan saja (jangan bikin error di PHP)
            // User tetap bisa simpan data, nanti tinggal restart python manual.
        }
    }
}