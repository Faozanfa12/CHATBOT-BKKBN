<?php

namespace App\Models;

use CodeIgniter\Model;

class FaqModel extends Model
{
    protected $table            = 'faq';
    protected $primaryKey       = 'id';
    
    // PERHATIKAN BARIS INI: Tambahkan 'key_name' ke dalam daftar
    protected $allowedFields    = ['parent_id', 'key_name', 'question_text', 'answer_text'];

    // ... (sisa kode pencarian searchLike biarkan saja)
    public function searchLike($keyword)
    {
        return $this->like('question_text', $keyword)
                    ->orLike('answer_text', $keyword)
                    ->findAll(5);
    }
}