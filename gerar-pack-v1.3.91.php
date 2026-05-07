<?php
/**
 * Gerador de Pack de Deploy — AvaliaFA v1.3.90
 * Features:
 *   - Reconhecimento facial nas provas (face-api.js, configurável por prova)
 *   - Webcam ativa durante toda a prova quando facial habilitado
 *   - Editor rico Quill com suporte a imagens nas questões
 * Uso: php gerar-pack-v1.3.90.php
 */

$packName    = 'avalia-fa_incremental_v1.3.91_facial-selfref.zip';
$projectRoot = __DIR__;
$outputDir   = $projectRoot . '/storage/app/updates/packages';
$outputPath  = $outputDir . '/' . $packName;

$files = [
    // Migrations
    'database/migrations/2026_04_21_000001_create_face_verifications_table.php',
    'database/migrations/2026_04_21_000002_add_face_fields_to_exams_and_exam_sessions.php',
    'database/migrations/2026_04_21_000003_change_questions_content_to_longtext.php',

    // Models
    'app/Models/FaceVerification.php',
    'app/Models/ExamSession.php',
    'app/Models/Exam.php',

    // Services
    'app/Services/FaceVerificationService.php',
    'app/Services/RiskScoreService.php',
    'app/Services/MoodleEnrollmentService.php',

    // Controllers
    'app/Http/Controllers/Api/V1/FaceVerificationController.php',
    'app/Http/Controllers/Api/V1/ExamSessionController.php',
    'app/Http/Controllers/Web/EstudanteController.php',
    'app/Http/Controllers/Web/ExamWebController.php',
    'app/Http/Controllers/Web/ProvaController.php',
    'app/Http/Controllers/Web/QuestaoController.php',

    // Rotas
    'routes/web.php',

    // JavaScript
    'resources/js/secure-exam-engine.js',

    // face-api.js — biblioteca + modelos (binários baixados)
    'public/vendor/face-api/face-api.min.js',
    'public/vendor/face-api/models/tiny_face_detector_model-weights_manifest.json',
    'public/vendor/face-api/models/tiny_face_detector_model-shard1',
    'public/vendor/face-api/models/face_landmark_68_tiny_model-weights_manifest.json',
    'public/vendor/face-api/models/face_landmark_68_tiny_model-shard1',
    'public/vendor/face-api/models/face_recognition_model-weights_manifest.json',
    'public/vendor/face-api/models/face_recognition_model-shard1',

    // Views
    'resources/views/layouts/exam.blade.php',
    'resources/views/provas/form.blade.php',
    'resources/views/estudantes/show.blade.php',
    'resources/views/questoes/form.blade.php',
    'resources/views/questoes/show.blade.php',
    'resources/views/exam/show.blade.php',
];

$missing = [];
foreach ($files as $file) {
    if (!file_exists($projectRoot . '/' . $file)) {
        $missing[] = $file;
    }
}

if ($missing) {
    echo "ERRO — arquivos não encontrados:\n";
    foreach ($missing as $f) {
        echo "  - $f\n";
    }
    exit(1);
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$zip = new ZipArchive();
if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    echo "ERRO — não foi possível criar: $outputPath\n";
    exit(1);
}

foreach ($files as $file) {
    $zip->addFile($projectRoot . '/' . $file, $file);
    echo "  + $file\n";
}

$zip->close();

$size = round(filesize($outputPath) / 1024, 1);
echo "\nPack gerado com sucesso!\n";
echo "Arquivo : $packName\n";
echo "Tamanho : {$size} KB\n";
echo "Local   : $outputPath\n";
echo "\nInstruções de deploy:\n";
echo "  1. Enviar o ZIP para o servidor via cPanel File Manager\n";
echo "  2. Extrair mantendo estrutura de diretórios\n";
echo "  3. REQUER migrate: php artisan migrate --force\n";
echo "  4. Rodar: php artisan config:cache && php artisan view:cache\n";
echo "  5. Verificar storage link: php artisan storage:link\n";
echo "  6. Testar:\n";
echo "       - Cadastrar foto de referência em Estudantes > [aluno] > Reconhecimento Facial\n";
echo "       - Criar prova com 'Reconhecimento facial' habilitado\n";
echo "       - Abrir questão e testar upload de imagem no editor Quill\n";
echo "\nNOTA: face-api.js e modelos (~5MB) incluídos no ZIP.\n";
