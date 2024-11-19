<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('employer_email_limits', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique(); // Unique email address
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employer_email_limits');
    }
};
