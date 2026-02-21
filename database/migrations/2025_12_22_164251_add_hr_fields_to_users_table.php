<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom HR utama
            if (!Schema::hasColumn('users', 'alamat_saat_ini')) {
                $table->string('alamat_saat_ini')->nullable()->after('level_position');
            }
            if (!Schema::hasColumn('users', 'institutional_formal_education')) {
                $table->string('institutional_formal_education')->nullable();
            }
            if (!Schema::hasColumn('users', 'formal_education')) {
                $table->string('formal_education')->nullable();
            }
            if (!Schema::hasColumn('users', 'study_program')) {
                $table->string('study_program')->nullable();
            }
            if (!Schema::hasColumn('users', 'start_date_formal_education')) {
                $table->date('start_date_formal_education')->nullable();
            }
            if (!Schema::hasColumn('users', 'end_date_formal_education')) {
                $table->date('end_date_formal_education')->nullable();
            }
            if (!Schema::hasColumn('users', 'non_formal_education')) {
                $table->string('non_formal_education')->nullable();
            }
            if (!Schema::hasColumn('users', 'types_non_formal_education')) {
                $table->string('types_non_formal_education')->nullable();
            }
            if (!Schema::hasColumn('users', 'program_name_non_formal')) {
                $table->string('program_name_non_formal')->nullable();
            }
            if (!Schema::hasColumn('users', 'institution_non_formal')) {
                $table->string('institution_non_formal')->nullable();
            }
            if (!Schema::hasColumn('users', 'working_experience')) {
                $table->string('working_experience')->nullable();
            }
            if (!Schema::hasColumn('users', 'company_working_experience')) {
                $table->string('company_working_experience')->nullable();
            }
            if (!Schema::hasColumn('users', 'job_position_working_experience')) {
                $table->string('job_position_working_experience')->nullable();
            }
            if (!Schema::hasColumn('users', 'job_responsibilities')) {
                $table->text('job_responsibilities')->nullable();
            }
            if (!Schema::hasColumn('users', 'social_media')) {
                $table->string('social_media')->nullable();
            }
            if (!Schema::hasColumn('users', 'url_social_media')) {
                $table->string('url_social_media')->nullable();
            }

            // Kolom dokumen
            if (!Schema::hasColumn('users', 'status_aktif')) {
                $table->string('status_aktif')->default('aktif')->after('posisi_id');
            }
            if (!Schema::hasColumn('users', 'ktp_file')) {
                $table->string('ktp_file')->nullable()->after('status_aktif');
            }
            if (!Schema::hasColumn('users', 'npwp_file')) {
                $table->string('npwp_file')->nullable()->after('ktp_file'); // ✅ DIPERBAIKI
            }
            if (!Schema::hasColumn('users', 'bpjs_file')) {
                $table->string('bpjs_file')->nullable()->after('npwp_file');
            }
            if (!Schema::hasColumn('users', 'kontrak_kerja_file')) {
                $table->string('kontrak_kerja_file')->nullable()->after('bpjs_file');
            }
            if (!Schema::hasColumn('users', 'history_mutasi')) {
                $table->text('history_mutasi')->nullable()->after('kontrak_kerja_file');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'alamat_saat_ini',
                'institutional_formal_education',
                'formal_education',
                'study_program',
                'start_date_formal_education',
                'end_date_formal_education',
                'non_formal_education',
                'types_non_formal_education',
                'program_name_non_formal',
                'institution_non_formal',
                'working_experience',
                'company_working_experience',
                'job_position_working_experience',
                'job_responsibilities',
                'social_media',
                'url_social_media',
                'status_aktif',
                'ktp_file',
                'npwp_file',
                'bpjs_file',
                'kontrak_kerja_file',
                'history_mutasi'
            ]);
        });
    }
};