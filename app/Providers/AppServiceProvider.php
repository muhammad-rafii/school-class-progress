<?php

namespace App\Providers;

use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix for Windows in `php artisan serve`:
        // 1. "SystemRoot" is mixed case on Windows, Winsock needs it.
        // 2. "TEMP" & "TMP" are needed so PHP knows where to store uploaded files.
        //    Without these, PHP falls back to C:\WINDOWS which is not writable,
        //    causing file uploads to fail with "The document failed to upload."
        if (windows_os()) {
            ServeCommand::$passthroughVariables[] = 'SystemRoot';
            ServeCommand::$passthroughVariables[] = 'TEMP';
            ServeCommand::$passthroughVariables[] = 'TMP';
            ServeCommand::$passthroughVariables[] = 'USERPROFILE';
        }

        // Pastikan kolom id_kelas ada pada tabel siswa dan pastikan role sistem lengkap
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('siswa') && !\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'id_kelas')) {
                \Illuminate\Support\Facades\Schema::table('siswa', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('id_kelas', 20)->nullable()->after('nama_siswa');
                });
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('role')) {
                \Illuminate\Support\Facades\DB::table('role')->updateOrInsert(['id_role' => 7], ['nama_role' => 'Guru BK']);
                \Illuminate\Support\Facades\DB::table('role')->updateOrInsert(['id_role' => 3], ['nama_role' => 'Wali Kelas']);
                \Illuminate\Support\Facades\DB::table('role')->updateOrInsert(['id_role' => 2], ['nama_role' => 'Guru Pengampu']);
                \Illuminate\Support\Facades\DB::table('role')->updateOrInsert(['id_role' => 4], ['nama_role' => 'Waka Kurikulum']);
                \Illuminate\Support\Facades\DB::table('role')->updateOrInsert(['id_role' => 5], ['nama_role' => 'Siswa']);
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('kelas') && !file_exists(storage_path('kelas_schema_dump.json'))) {
                @file_put_contents(storage_path('kelas_schema_dump.json'), json_encode([
                    'kelas_columns' => \Illuminate\Support\Facades\Schema::getColumnListing('kelas'),
                    'jurusan_columns' => \Illuminate\Support\Facades\Schema::hasTable('jurusan') ? \Illuminate\Support\Facades\Schema::getColumnListing('jurusan') : [],
                    'kelas_rows' => \Illuminate\Support\Facades\DB::table('kelas')->take(10)->get(),
                    'jurusan_rows' => \Illuminate\Support\Facades\Schema::hasTable('jurusan') ? \Illuminate\Support\Facades\DB::table('jurusan')->get() : [],
                ], JSON_PRETTY_PRINT));
            }
        } catch (\Throwable $e) {
            // Abaikan error saat database tidak terhubung di mode CLI
        }
    }
}
