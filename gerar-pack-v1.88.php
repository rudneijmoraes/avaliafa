<?php
/**
 * Gerador de Pack de Deploy — AvaliaFA v1.88
 * Módulo: Dicas do Professor + Materiais Complementares
 * Uso: php gerar-pack-v1.88.php
 */

$packName    = 'avalia-fa_incremental_v1.88_dicas-materiais.zip';
$projectRoot = __DIR__;
$outputDir   = $projectRoot . '/storage/app/updates/packages';
$outputPath  = $outputDir . '/' . $packName;

$files = [
    // Migrations
    'database/migrations/2026_04_20_000001_create_teacher_tips_table.php',
    'database/migrations/2026_04_20_000002_create_learning_materials_table.php',

    // Models
    'app/Models/TeacherTip.php',
    'app/Models/LearningMaterial.php',

    // Controllers — Admin
    'app/Http/Controllers/Web/TeacherTipController.php',
    'app/Http/Controllers/Web/LearningMaterialController.php',

    // Controllers — Portal do Aluno
    'app/Http/Controllers/Web/StudentTipController.php',
    'app/Http/Controllers/Web/StudentMaterialController.php',

    // Rotas
    'routes/web.php',

    // Views — Admin
    'resources/views/conteudo/dicas/index.blade.php',
    'resources/views/conteudo/dicas/form.blade.php',
    'resources/views/conteudo/materiais/index.blade.php',
    'resources/views/conteudo/materiais/form.blade.php',

    // Views — Portal do Aluno
    'resources/views/student/tips/index.blade.php',
    'resources/views/student/materials/index.blade.php',

    // Views — Modificadas
    'resources/views/layouts/app.blade.php',
    'resources/views/configuracoes/index.blade.php',
];

// Verifica arquivos faltando
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

// Cria o ZIP
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
echo "  1. Fazer backup do banco e dos arquivos em produção\n";
echo "  2. Enviar o ZIP para o servidor via cPanel File Manager\n";
echo "  3. Extrair mantendo estrutura de diretórios\n";
echo "  4. Rodar: php artisan migrate\n";
echo "  5. Rodar: php artisan config:cache && php artisan view:cache\n";
echo "  6. Verificar em /configuracoes os novos cards de Dicas e Materiais\n";
