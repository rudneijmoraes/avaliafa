<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulado_hubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_system_id')->constrained('client_systems')->cascadeOnDelete();
            $table->string('name', 180);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->string('landing_title', 180)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['client_system_id', 'slug']);
            $table->index(['client_system_id', 'status']);
        });

        Schema::table('simulados', function (Blueprint $table) {
            $table->foreignId('hub_id')
                ->nullable()
                ->after('client_system_id')
                ->constrained('simulado_hubs')
                ->nullOnDelete();
            $table->unsignedInteger('hub_order')->default(1)->after('hub_id');
            $table->index(['hub_id', 'hub_order']);
        });

        $now = now();
        $simulados = DB::table('simulados')
            ->whereNull('deleted_at')
            ->orderBy('client_system_id')
            ->orderBy('id')
            ->get();

        $hubIdsByKey = [];
        $hubOrdersByHubId = [];

        foreach ($simulados as $simulado) {
            $settings = json_decode((string) ($simulado->settings ?? 'null'), true) ?: [];
            $rawHubSlug = trim((string) data_get($settings, 'public_hub_slug', ''));
            $hubSlug = Str::slug($rawHubSlug !== '' ? $rawHubSlug : $simulado->slug);
            $hubSlug = $hubSlug !== '' ? $hubSlug : 'simulado-'.$simulado->id;
            $hubName = trim((string) data_get($settings, 'weekly_label', '')) ?: $simulado->name;
            $hubKey = $simulado->client_system_id.'|'.$hubSlug;

            if (! isset($hubIdsByKey[$hubKey])) {
                $existingHubId = DB::table('simulado_hubs')
                    ->where('client_system_id', $simulado->client_system_id)
                    ->where('slug', $hubSlug)
                    ->value('id');

                if (! $existingHubId) {
                    $existingHubId = DB::table('simulado_hubs')->insertGetId([
                        'client_system_id' => $simulado->client_system_id,
                        'name' => $hubName,
                        'slug' => $hubSlug,
                        'description' => $simulado->description,
                        'landing_title' => $hubName,
                        'status' => $simulado->status === 'archived' ? 'archived' : 'active',
                        'sort_order' => 1,
                        'settings' => json_encode([
                            'legacy_public_hub_slug' => $rawHubSlug,
                        ], JSON_UNESCAPED_UNICODE),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $hubIdsByKey[$hubKey] = $existingHubId;
                $hubOrdersByHubId[$existingHubId] = 0;
            }

            $hubId = $hubIdsByKey[$hubKey];
            $hubOrdersByHubId[$hubId] = ($hubOrdersByHubId[$hubId] ?? 0) + 1;

            DB::table('simulados')
                ->where('id', $simulado->id)
                ->update([
                    'hub_id' => $hubId,
                    'hub_order' => $hubOrdersByHubId[$hubId],
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('simulados', function (Blueprint $table) {
            $table->dropIndex('simulados_hub_id_hub_order_index');
            $table->dropConstrainedForeignId('hub_id');
            $table->dropColumn('hub_order');
        });

        Schema::dropIfExists('simulado_hubs');
    }
};
