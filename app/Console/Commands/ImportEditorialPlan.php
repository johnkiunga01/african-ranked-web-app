<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\EditorialPlanImport;
use App\Services\EditorialWorkbook;
use Illuminate\Console\Command;

class ImportEditorialPlan extends Command
{
    protected $signature = 'editorial:import-plan {path} {--actor=} {--commit : Save private briefs after inspecting the dry-run}';

    protected $description = 'Preview or import the owner workbook without changing existing articles or publication pointers.';

    public function handle(EditorialWorkbook $reader, EditorialPlanImport $importer): int
    {
        $path = $this->argument('path');
        $intake = $reader->read($path, basename($path));
        $this->line(count($intake['records']).' briefs; '.$intake['drafts'].' team drafts; SHA256 '.$intake['hash']);
        if (! $this->option('commit')) {
            $this->line('Dry run only. No database records changed.');

            return self::SUCCESS;
        }
        $actor = User::where('email', mb_strtolower(trim($this->option('actor') ?? '')))->first();
        if (! $actor?->isSuperAdmin()) {
            $this->error('Specify an existing active verified super admin with --actor.');

            return self::FAILURE;
        }
        $this->line($importer->commit($actor, $intake).' private briefs saved. Existing CMS content and publication remain intact.');

        return self::SUCCESS;
    }
}
