<?php

namespace App\Console\Commands;

use App\Enums\StaffRole;
use App\Services\StaffInvitations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class InviteSuperAdmin extends Command
{
    protected $signature = 'staff:invite-admin {email} {--name=Super Admin} {--link-file=}';

    protected $description = 'Create a one-time administrator setup link in private storage; never print a password or token.';

    public function handle(StaffInvitations $invitations): int
    {
        $data = ['email' => $this->argument('email'), 'name' => $this->option('name')];
        $validator = Validator::make($data, ['email' => ['required', 'email', 'max:254'], 'name' => ['required', 'string', 'max:100']]);
        if ($validator->fails()) {
            $this->error('Provide a valid email address and name.');

            return self::FAILURE;
        }
        $path = $this->option('link-file') ?: storage_path('app/private/super-admin-setup.url');
        $privateRoot = storage_path('app/private');
        File::ensureDirectoryExists($privateRoot, 0700);
        if (! str_starts_with($path, $privateRoot.'/') || str_contains($path, '..') || File::exists($path)) {
            $this->error('Choose a new file inside storage/app/private; existing files are never overwritten.');

            return self::FAILURE;
        }
        [, $url] = $invitations->issue($data['email'], $data['name'], StaffRole::SuperAdmin);
        File::put($path, $url.PHP_EOL);
        chmod($path, 0600);
        $this->info('Administrator invitation created. The one-time setup URL is saved in private storage.');
        $this->line('Open the requested link file locally and share it only with the intended administrator. It expires in 24 hours.');

        return self::SUCCESS;
    }
}
