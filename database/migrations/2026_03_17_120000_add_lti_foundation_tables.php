<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lti_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_system_id')->nullable()->constrained('client_systems')->nullOnDelete();
            $table->string('issuer');
            $table->string('client_id');
            $table->string('deployment_id')->nullable();
            $table->string('platform_name')->nullable();
            $table->string('auth_login_url')->nullable();
            $table->string('auth_token_url')->nullable();
            $table->string('keyset_url')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['issuer', 'client_id', 'deployment_id'], 'lti_registrations_platform_unique');
        });

        Schema::create('lti_resource_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lti_registration_id')->constrained('lti_registrations')->cascadeOnDelete();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->string('resource_link_id');
            $table->string('context_id')->nullable();
            $table->string('context_label')->nullable();
            $table->string('context_title')->nullable();
            $table->string('lineitem_url')->nullable();
            $table->string('lineitems_url')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['lti_registration_id', 'resource_link_id'], 'lti_resource_links_unique');
        });

        Schema::create('lti_launch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lti_registration_id')->nullable()->constrained('lti_registrations')->nullOnDelete();
            $table->foreignId('lti_resource_link_id')->nullable()->constrained('lti_resource_links')->nullOnDelete();
            $table->foreignId('exam_session_id')->nullable()->constrained('exam_sessions')->nullOnDelete();
            $table->string('message_type')->nullable();
            $table->string('user_sub')->nullable();
            $table->string('user_email')->nullable();
            $table->json('roles')->nullable();
            $table->json('claims')->nullable();
            $table->boolean('is_successful')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('launched_at')->nullable();
            $table->timestamps();
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('launch_source', 32)->nullable()->after('token_jti');
            $table->foreignId('lti_registration_id')->nullable()->after('launch_source')->constrained('lti_registrations')->nullOnDelete();
            $table->foreignId('lti_resource_link_id')->nullable()->after('lti_registration_id')->constrained('lti_resource_links')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lti_resource_link_id');
            $table->dropConstrainedForeignId('lti_registration_id');
            $table->dropColumn('launch_source');
        });

        Schema::dropIfExists('lti_launch_logs');
        Schema::dropIfExists('lti_resource_links');
        Schema::dropIfExists('lti_registrations');
    }
};
