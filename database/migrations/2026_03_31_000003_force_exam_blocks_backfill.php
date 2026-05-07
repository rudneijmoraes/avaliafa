<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_blocks')) {
            Schema::create('exam_blocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
                $table->string('title', 120)->nullable();
                $table->longText('base_text')->nullable();
                $table->unsignedSmallInteger('order')->default(1);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['exam_id', 'order']);
            });
        }

        if (! Schema::hasColumn('exam_questions', 'exam_block_id')) {
            Schema::table('exam_questions', function (Blueprint $table) {
                $table->foreignId('exam_block_id')
                    ->nullable()
                    ->after('exam_id')
                    ->constrained('exam_blocks')
                    ->nullOnDelete();
            });
        }

        $now = now();
        $examIds = DB::table('exam_questions')
            ->select('exam_id')
            ->distinct()
            ->pluck('exam_id');

        foreach ($examIds as $examId) {
            $blockId = DB::table('exam_blocks')
                ->where('exam_id', $examId)
                ->orderBy('order')
                ->value('id');

            if (! $blockId) {
                $blockId = DB::table('exam_blocks')->insertGetId([
                    'exam_id' => $examId,
                    'title' => 'Bloco 1',
                    'base_text' => null,
                    'order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]);
            }

            DB::table('exam_questions')
                ->where('exam_id', $examId)
                ->whereNull('exam_block_id')
                ->update([
                    'exam_block_id' => $blockId,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
    }
};
