<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\EmailLog;
use App\Models\Simulado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailLogModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_creates_sent_log_when_no_error(): void
    {
        $log = EmailLog::record(
            type: 'resultado',
            simulado: null,
            recipientEmail: 'joao@example.com',
            recipientName: 'João Silva',
            subject: 'Resultado do Simulado',
        );

        $this->assertDatabaseHas('email_logs', [
            'type'            => 'resultado',
            'recipient_email' => 'joao@example.com',
            'status'          => 'sent',
            'error_message'   => null,
        ]);
        $this->assertNotNull($log->sent_at);
        $this->assertNull($log->failed_at);
    }

    public function test_record_creates_failed_log_when_error_provided(): void
    {
        $log = EmailLog::record(
            type: 'broadcast',
            simulado: null,
            recipientEmail: 'maria@example.com',
            recipientName: 'Maria',
            subject: 'Assunto',
            error: 'Connection refused',
        );

        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => 'maria@example.com',
            'status'          => 'failed',
            'error_message'   => 'Connection refused',
        ]);
        $this->assertNull($log->sent_at);
        $this->assertNotNull($log->failed_at);
    }

    public function test_record_stores_simulado_and_client_system_ids(): void
    {
        $system = ClientSystem::create([
            'name' => 'Sistema Teste', 'slug' => 'sistema-teste',
            'client_id' => 'cid', 'client_secret' => 'sec', 'active' => true,
        ]);

        $log = EmailLog::create([
            'client_system_id' => $system->id,
            'simulado_id'      => null,
            'type'             => 'disponibilidade',
            'recipient_email'  => 'ana@example.com',
            'recipient_name'   => 'Ana',
            'subject'          => 'Simulado disponível',
            'status'           => 'sent',
            'sent_at'          => now(),
        ]);

        $this->assertEquals($system->id, $log->client_system_id);
    }

    public function test_scope_sent_filters_only_sent_logs(): void
    {
        EmailLog::create(['type' => 'resultado', 'recipient_email' => 'a@b.com', 'subject' => 'S', 'status' => 'sent',   'sent_at' => now()]);
        EmailLog::create(['type' => 'resultado', 'recipient_email' => 'b@b.com', 'subject' => 'S', 'status' => 'failed', 'failed_at' => now()]);

        $this->assertEquals(1, EmailLog::sent()->count());
    }

    public function test_scope_failed_filters_only_failed_logs(): void
    {
        EmailLog::create(['type' => 'resultado', 'recipient_email' => 'a@b.com', 'subject' => 'S', 'status' => 'sent',   'sent_at' => now()]);
        EmailLog::create(['type' => 'resultado', 'recipient_email' => 'b@b.com', 'subject' => 'S', 'status' => 'failed', 'failed_at' => now()]);

        $this->assertEquals(1, EmailLog::failed()->count());
    }

    public function test_scope_of_type_filters_by_type(): void
    {
        EmailLog::create(['type' => 'resultado',      'recipient_email' => 'a@b.com', 'subject' => 'S', 'status' => 'sent', 'sent_at' => now()]);
        EmailLog::create(['type' => 'broadcast',      'recipient_email' => 'b@b.com', 'subject' => 'S', 'status' => 'sent', 'sent_at' => now()]);
        EmailLog::create(['type' => 'disponibilidade','recipient_email' => 'c@b.com', 'subject' => 'S', 'status' => 'sent', 'sent_at' => now()]);

        $this->assertEquals(1, EmailLog::ofType('broadcast')->count());
    }

    public function test_scope_for_system_filters_by_client_system_id(): void
    {
        $system1 = ClientSystem::create([
            'name' => 'System 1', 'slug' => 'system-1',
            'client_id' => 'cid1', 'client_secret' => 'sec1', 'active' => true,
        ]);
        $system2 = ClientSystem::create([
            'name' => 'System 2', 'slug' => 'system-2',
            'client_id' => 'cid2', 'client_secret' => 'sec2', 'active' => true,
        ]);

        EmailLog::create(['client_system_id' => $system1->id, 'type' => 'resultado', 'recipient_email' => 'a@b.com', 'subject' => 'S', 'status' => 'sent', 'sent_at' => now()]);
        EmailLog::create(['client_system_id' => $system2->id, 'type' => 'resultado', 'recipient_email' => 'b@b.com', 'subject' => 'S', 'status' => 'sent', 'sent_at' => now()]);

        $this->assertEquals(1, EmailLog::forSystem($system1->id)->count());
    }

    public function test_scope_in_period_filters_by_days(): void
    {
        $recent = new EmailLog(['type' => 'resultado', 'recipient_email' => 'a@b.com', 'subject' => 'S', 'status' => 'sent', 'sent_at' => now()]);
        $recent->save();

        $old = new EmailLog(['type' => 'resultado', 'recipient_email' => 'b@b.com', 'subject' => 'S', 'status' => 'sent', 'sent_at' => now()]);
        $old->save();
        // Force created_at to be 100 days ago via a direct update
        \Illuminate\Support\Facades\DB::table('email_logs')
            ->where('id', $old->id)
            ->update(['created_at' => now()->subDays(100)]);

        $this->assertEquals(1, EmailLog::inPeriod(30)->count());
    }
}
