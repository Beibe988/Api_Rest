<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('User_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('User_profiles', 'display_name')) {
                $table->string('display_name')->nullable()->after('id_user');
            }
            if (!Schema::hasColumn('User_profiles', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('display_name');
            }
            if (!Schema::hasColumn('User_profiles', 'phone')) {
                $table->string('phone', 32)->nullable()->after('birth_date');
            }

            if (!Schema::hasColumn('User_profiles', 'street')) {
                $table->string('street')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('User_profiles', 'city')) {
                $table->string('city')->nullable()->after('street');
            }
            if (!Schema::hasColumn('User_profiles', 'province')) {
                $table->string('province', 64)->nullable()->after('city');
            }
            if (!Schema::hasColumn('User_profiles', 'postal_code')) {
                $table->string('postal_code', 16)->nullable()->after('province');
            }
            if (!Schema::hasColumn('User_profiles', 'country')) {
                $table->string('country', 64)->nullable()->after('postal_code');
            }

            // opzionali ma utili (se non li hai già)
            if (!Schema::hasColumn('User_profiles', 'locale')) {
                $table->string('locale', 8)->nullable()->after('country');
            }
            if (!Schema::hasColumn('User_profiles', 'timezone')) {
                $table->string('timezone', 64)->nullable()->after('locale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('User_profiles', function (Blueprint $table) {
            // Droppa in sicurezza solo se esistono
            foreach ([
                'display_name','birth_date','phone',
                'street','city','province','postal_code','country',
                'locale','timezone'
            ] as $col) {
                if (Schema::hasColumn('User_profiles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
