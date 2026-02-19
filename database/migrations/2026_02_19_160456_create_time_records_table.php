<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('date');
            $table->decimal('hours', 4, 1)->default(0); // Часы (например, 8.0, 4.5)
            $table->string('reason')->nullable(); // Причина отсутствия (vacation, sick_leave и т.д.)
            $table->text('notes')->nullable(); // Примечания
            $table->timestamps();

            // Уникальность - одна запись на сотрудника в день
            $table->unique(['employee_id', 'date']);

            // Внешний ключ
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_records');
    }
};
