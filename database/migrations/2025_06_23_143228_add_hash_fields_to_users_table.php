<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Campi hash per ricerche e confronti
            $table->string('hash_name', 64)->nullable()->after('name');
            $table->string('hash_surname', 64)->nullable()->after('surname');
            $table->string('hash_email', 64)->nullable()->after('email');
        });
    }
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['hash_name', 'hash_surname', 'hash_email']);
        });
    }

};
