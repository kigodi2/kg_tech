<?php

namespace App\Console\Commands;

use App\Models\ResultPortalLink;
use Illuminate\Console\Command;

class GenerateResultsPortalLink extends Command
{
    protected $signature = 'results-portal:generate-link
        {name : Friendly portal name}
        {--school_id= : Optional school ID}
        {--exam_id= : Optional exam ID}
        {--region_id= : Optional region ID}
        {--created_by= : Optional creator user ID}
        {--expires_days=30 : Number of days before expiry (empty for no expiry)}
        {--meta_json= : Optional JSON metadata}';

    protected $description = 'Generate a secure public results portal link';

    public function handle(): int
    {
        $meta = [];
        $rawMeta = $this->option('meta_json');

        if ($rawMeta) {
            $decoded = json_decode((string) $rawMeta, true);
            if (!is_array($decoded)) {
                $this->error('Invalid --meta_json payload.');
                return self::FAILURE;
            }
            $meta = $decoded;
        }

        $expiresDays = $this->option('expires_days');
        $expiresAt = null;

        if ($expiresDays !== null && $expiresDays !== '') {
            if (!is_numeric($expiresDays) || (int) $expiresDays < 1) {
                $this->error('--expires_days must be a positive integer or empty.');
                return self::FAILURE;
            }
            $expiresAt = now()->addDays((int) $expiresDays);
        }

        $token = bin2hex(random_bytes(24));

        $link = ResultPortalLink::create([
            'name' => (string) $this->argument('name'),
            'school_id' => $this->option('school_id') ?: null,
            'exam_id' => $this->option('exam_id') ?: null,
            'region_id' => $this->option('region_id') ?: null,
            'created_by' => $this->option('created_by') ?: null,
            'token_hash' => hash('sha256', $token),
            'meta_json' => $meta,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        $this->info('Link created: #' . $link->id);
        $this->line(route('public.results.portal', ['token' => $token]));

        return self::SUCCESS;
    }
}
