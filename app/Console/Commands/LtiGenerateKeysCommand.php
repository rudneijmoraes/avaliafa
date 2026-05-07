<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LtiGenerateKeysCommand extends Command
{
    protected $signature = 'lti:generate-keys
                            {--force : Sobrescrever chaves existentes}';

    protected $description = 'Gera par de chaves RSA para LTI 1.3 (oauth-private.key e oauth-public.key)';

    public function handle(): int
    {
        $privatePath = config('lti.private_key_path', storage_path('oauth-private.key'));
        $publicPath = config('lti.public_key_path', storage_path('oauth-public.key'));

        if (! $this->option('force') && (is_file($privatePath) || is_file($publicPath))) {
            $this->error('Chaves ja existem. Use --force para sobrescrever.');

            return self::FAILURE;
        }

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);

        if ($resource === false) {
            $this->error('Falha ao gerar chave RSA: '.openssl_error_string());

            return self::FAILURE;
        }

        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);
        $publicKey = $details['key'];

        file_put_contents($privatePath, $privateKey);
        file_put_contents($publicPath, $publicKey);

        chmod($privatePath, 0600);
        chmod($publicPath, 0644);

        $this->info('Chaves RSA geradas com sucesso:');
        $this->line("  Private: {$privatePath}");
        $this->line("  Public:  {$publicPath}");
        $this->newLine();
        $this->info('Key ID (kid): '.config('lti.tool_key_id', 'avaliafa-lti'));
        $this->info('JWKS URL: '.url('/lti/jwks'));

        return self::SUCCESS;
    }
}
