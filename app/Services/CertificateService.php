<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TCPDF;

class CertificateService
{
    public function generatePdf(Certificate $certificate): Certificate
    {
        $certificate->loadMissing(['student:id,name,first_name,last_name,cpf', 'exam:id,title']);

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('AvaliaFA');
        $pdf->SetAuthor('Faculdade Anasps');
        $pdf->SetTitle('Certificado '.$certificate->code);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(14, 14, 14);
        $pdf->AddPage();

        $studentName = trim((string) ($certificate->student?->name ?? 'Estudante'));
        $examTitle = (string) ($certificate->exam?->title ?? 'Avaliação');
        $score = number_format((float) $certificate->final_score, 2, ',', '.');
        $issueDate = $certificate->issued_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');

        $html = '
            <div style="border:3px solid #111827;padding:32px 36px;height:100%;">
                <div style="text-align:center;margin-bottom:24px;">
                    <div style="font-size:34px;font-weight:bold;color:#111827;letter-spacing:0.5px;">CERTIFICADO</div>
                    <div style="font-size:12px;color:#6B7280;margin-top:4px;">AvaliaFA — Faculdade Anasps</div>
                </div>
                <div style="font-size:15px;line-height:1.7;color:#1F2937;text-align:justify;">
                    Certificamos que <b>'.$this->escape($studentName).'</b> concluiu com aproveitamento a avaliação
                    <b>'.$this->escape($examTitle).'</b>, obtendo nota final <b>'.$score.'</b>.
                </div>
                <div style="margin-top:28px;font-size:13px;color:#374151;">
                    Código de verificação: <b>'.$certificate->code.'</b><br>
                    Emitido em: <b>'.$issueDate.'</b>
                </div>
                <div style="margin-top:42px;display:flex;justify-content:space-between;align-items:flex-end;">
                    <div style="font-size:11px;color:#6B7280;">
                        Valide em:<br>
                        '.url('/certificados/'.$certificate->code.'/verificar').'
                    </div>
                    <div style="text-align:center;">
                        <div style="border-top:1px solid #111827;width:230px;margin-bottom:6px;"></div>
                        <div style="font-size:11px;color:#374151;">Assinatura digital AvaliaFA</div>
                    </div>
                </div>
            </div>
        ';

        $pdf->SetFont('helvetica', '', 12);
        $pdf->writeHTML($html, true, false, true, false, '');

        $binary = $pdf->Output('', 'S');

        $path = 'certificates/certificate-'.$certificate->id.'-'.Str::lower(Str::uuid()->toString()).'.pdf';
        Storage::disk('public')->put($path, $binary);

        $certificate->update([
            'pdf_path' => $path,
            'sha256_hash' => hash('sha256', $binary),
        ]);

        return $certificate->fresh();
    }

    private function escape(string $value): string
    {
        return e($value);
    }
}
