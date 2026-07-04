<?php 

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration {
    public function up() {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT', 
                'constraint'     => 11, 
                'unsigned'       => true, // Tambahan standar CI4
                'auto_increment' => true
            ],
            'username' => [
                'type'       => 'VARCHAR', 
                'constraint' => 100
            ],
            'password' => [
                'type'       => 'VARCHAR', 
                'constraint' => 255
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users', true);
    }

    public function down() { 
        $this->forge->dropTable('users'); 
    }
}