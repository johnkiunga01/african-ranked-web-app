<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * One-time direct SMTP handshake; never queued or scheduled.
 * Do not print credentials or transport exception details.
 */
class SendSmtpSmokeTest extends Command
{
    protected $signature = 'ops:mail-smoke {--to= : A verified inbox expressly approved for the test}';

    protected $description = 'Send one transactional SMTP2GO test message and report only whether the SMTP relay accepted it.';

    public function handle(): int
    {
        $recipient = trim((string) $this->option('to'));
        $from = (string) config('mail.from.address');
        $mailer = config('mail.mailers.smtp', []);
        $host = strtolower((string) ($mailer['host'] ?? ''));
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL) || ! str_ends_with($host, 'smtp2go.com')
            || ! filled($mailer['username'] ?? null) || ! filled($mailer['password'] ?? null)
            || $from !== 'john@salibay.com') {
            $this->error('AR_SMTP_SMOKE '.json_encode(['smtp_accepted' => false, 'reason' => 'smtp_configuration_or_verified_sender_missing']));

            return self::FAILURE;
        }
        try {
            Mail::mailer('smtp')->raw(
                "African Ranked SMTP2GO connection test.\nThis verifies SMTP relay acceptance only, not inbox placement or password-reset workflows.\nNo action is required.",
                function ($message) use ($recipient) {
                    $message->to($recipient)->subject('African Ranked — SMTP2GO delivery test');
                }
            );
            $receipt = ['smtp_accepted' => true, 'from' => $from, 'to' => $recipient, 'inbox_verified' => false];
            $this->info('AR_SMTP_SMOKE '.json_encode($receipt));
            Log::info('AR_SMTP_SMOKE '.json_encode($receipt));

            return self::SUCCESS;
        } catch (Throwable $e) {
            // Exception messages sometimes contain SMTP server responses or auth details.
            $receipt = ['smtp_accepted' => false, 'failure_class' => class_basename($e)];
            $this->error('AR_SMTP_SMOKE '.json_encode($receipt));
            Log::warning('AR_SMTP_SMOKE '.json_encode($receipt));

            return self::FAILURE;
        }
    }
}
