<?php
/**
 * Gerador de Pack de Deploy — AvaliaFA v1.3.89
 * Feature: Reset de senha do aluno pelo painel admin (volta ao CPF sem formatação)
 * Uso: php gerar-pack-v1.3.89.php
 */

$packName    = 'avalia-fa_incremental_v1.3.89_reset-senha-aluno.zip';
$projectRoot = __DIR__;
$outputDir   = $projectRoot . '/storage/app/updates/packages';
$outputPath  = $outputDir . '/' . $packName;

$files = [
    'routes/web.php',
    'app/Http/Controllers/Web/EstudanteController.php',
    'resources/views/estudantes/show.blade.php',
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
echo "  3. NÃO requer migrate (sem novas migrations)\n";
echo "  4. Rodar: php artisan config:cache && php artisan view:cache\n";
echo "  5. Testar: abrir perfil de um aluno e verificar botão 'Resetar Senha'\n";
