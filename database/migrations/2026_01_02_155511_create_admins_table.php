<?php
// database/migrations/[timestamp]_create_admins_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('password');
            $table->foreignId('employee_id')
                ->unique()
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->enum('role', ['super_admin', 'department_admin'])->default('department_admin');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
