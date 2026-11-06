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
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'id_kelas')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('id_kelas', 20)->nullable()->after('id_role');
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
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'id_kelas')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasTable('kelas')) {
                    $table->dropForeign(['id_kelas']);
                }
                $table->dropColumn('id_kelas');
            });
        }
    }
};
