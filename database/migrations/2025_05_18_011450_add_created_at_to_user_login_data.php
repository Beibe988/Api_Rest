<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_login_data', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->after('secret_jwt');
        });
    }

    public function down(): void
    {
        Schema::table('user_login_data', function (Blueprint $table) {
            $table->dropColumn('created_at');
        });
    }
};

