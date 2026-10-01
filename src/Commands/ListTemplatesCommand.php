<?php

namespace Olorunda\ZohoCpaas\Commands;

use Illuminate\Console\Command;
use Olorunda\ZohoCpaas\Facades\ZohoCpaas;
use Throwable;

class ListTemplatesCommand extends Command
{
    protected $signature = 'zoho:list-templates
                            {agent? : Mail Agent alias (defaults to config)}
                            {--offset=0 : Offset for pagination}
                            {--limit=25 : Number of templates to fetch}';

    protected $description = 'List all email templates for a ZeptoMail Mail Agent';

    public function handle(): int
    {
        $agent = $this->argument('agent');
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');

        $this->info("Fetching email templates for agent: " . ($agent ?: 'default') . "...");

        try {
            $response = ZohoCpaas::templates()->all($agent, $offset, $limit);
            $templates = $response['data'] ?? [];

            if (empty($templates)) {
                $this->warn('No templates found.');
                return Command::SUCCESS;
            }

            $rows = [];
            foreach ($templates as $t) {
                $rows[] = [
                    $t['template_name'] ?? 'N/A',
                    $t['template_key'] ?? 'N/A',
                    $t['template_alias'] ?? 'N/A',
                    $t['subject'] ?? 'N/A',
                    $t['created_time'] ?? 'N/A',
                ];
            }

            $this->table(['Name', 'Key', 'Alias', 'Subject', 'Created At'], $rows);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to fetch templates: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
