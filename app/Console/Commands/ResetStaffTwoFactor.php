<?php

namespace App\Console\Commands;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetStaffTwoFactor extends Command
{
    protected $signature = 'staff:reset-two-factor {email} {--reason=} {--force}';

    protected $description = 'Trusted-server recovery only: revoke authenticator/recovery codes and sessions after offline identity verification.';

    public function handle(): int
    {
        $reason = trim((string) $this->option('reason'));
        if (! $this->option('force') || mb_strlen($reason) < 20 || mb_strlen($reason) > 1000) {
            $this->error('Trusted operator recovery requires --force and a 20–1,000 character --reason describing the offline identity check. No account was changed.');

            return self::FAILURE;
        }
        $user = User::where('email', mb_strtolower(trim($this->argument('email'))))->first();
        if (! $user) {
            $this->error('Account not found. No account was changed.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($user, $reason) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $user->two_factor_secret = null;
            $user->two_factor_pending_secret = null;
            $user->two_factor_pending_expires_at = null;
            $user->two_factor_confirmed_at = null;
            $user->two_factor_recovery_codes = null;
            $user->two_factor_last_counter = null;
            $user->session_version++;
            $user->remember_token = Str::random(60);
            $user->save();
            AuditEvent::record(null, 'staff.mfa_operator_reset', $user, ['reason' => $reason]);
        });
        $this->info('Authenticator and recovery codes revoked; all existing sessions invalidated. Password, role, provider keys and articles were preserved.');
        $this->line('The owner must sign in and enroll a new authenticator. Production administrator enrollment remains mandatory.');

        return self::SUCCESS;
    }
}
