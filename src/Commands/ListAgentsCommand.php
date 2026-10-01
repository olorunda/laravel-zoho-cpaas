<?php

namespace Olorunda\ZohoCpaas\Commands;

use Illuminate\Console\Command;
use Olorunda\ZohoCpaas\Facades\ZohoCpaas;
use Throwable;

class ListAgentsCommand extends Command
{
    protected $signature = 'zoho:list-agents';

    protected $description = 'List all ZeptoMail Mail Agents for the configured Zoho account';

    public function handle(): int
    {
        $this->info('Fetching Zoho CPaaS / ZeptoMail agents...');

        try {
            $response = ZohoCpaas::agents()->all();
            $agents = $response['data'] ?? [];

            if (empty($agents)) {
                $this->warn('No mail agents found.');
                return Command::SUCCESS;
            }

            $rows = [];
            foreach ($agents as $agent) {
                $rows[] = [
                    $agent['mailagent_name'] ?? 'N/A',
                    $agent['mailagent_key'] ?? 'N/A',
                    $agent['status'] ?? 'N/A',
                    $agent['created_time'] ?? 'N/A',
                    $agent['description'] ?? '',
                ];
            }

            $this->table(['Name', 'Key', 'Status', 'Created At', 'Description'], $rows);

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to fetch agents: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
