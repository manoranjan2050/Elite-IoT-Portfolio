<?php

namespace App\Console\Commands;

use App\Services\SignatureService;
use Illuminate\Console\Command;
use RuntimeException;

class KavachKeys extends Command
{
    protected $signature = 'kavach:keys {--force : Overwrite an existing keypair}';

    protected $description = 'Generate the Ed25519 keypair used to sign license payloads';

    public function handle(SignatureService $signatures): int
    {
        try {
            $result = $signatures->generateKeypair((bool) $this->option('force'));
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Keypair generated. Private key: storage/app/private/keys/signing.key (NEVER share, NEVER commit).');
        $this->newLine();
        $this->line('Public key — paste this into KavachClient config of every product:');
        $this->newLine();
        $this->line($result['public']);

        return self::SUCCESS;
    }
}
