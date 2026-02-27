<?php
// database/migrations/[timestamp]_create_employees_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('tab_number')->unique();
            $table->string('position')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->date('hire_date')->nullable();
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments') // Теперь departments существует
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
