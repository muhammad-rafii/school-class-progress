<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('siswa') && !Schema::hasColumn('siswa', 'id_kelas')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->string('id_kelas', 20)->nullable()->after('nama_siswa');
                if (Schema::hasTable('kelas')) {
                    $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'id_kelas')) {
            Schema::table('siswa', function (Blueprint $table) {
                try {
                    $table->dropForeign(['id_kelas']);
                } catch (\Throwable $e) {}
                $table->dropColumn('id_kelas');
            });
        }
    }
};

