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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name'); // Имя
            $table->string('last_name'); // Фамилия
            $table->string('middle_name')->nullable(); // Отчество
            $table->string('tab_number')->unique(); // ТАБЕЛЬНЫЙ НОМЕР
            $table->unsignedBigInteger('department_id')->nullable(); // ID отдела
            $table->timestamps();

            // Внешний ключ на departments (уже существует)
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
