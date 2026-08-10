<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestMail extends Command
{
    protected $signature = 'mail:test {email : Alamat email tujuan tes}';

    protected $description = 'Kirim email tes untuk memverifikasi konfigurasi SMTP (Mailtrap)';

    public function handle(): int
    {
        $email = $this->argument('email');

        $this->info("Mengirim email tes ke {$email}...");
        $this->line('Mailer : ' . config('mail.default'));
        $this->line('Host   : ' . config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port'));
        $this->line('From   : ' . config('mail.from.address') . ' <' . config('mail.from.name') . '>');

        try {
            Mail::raw(
                'Halo! Ini email tes dari aplikasi Ujian Online. Jika kamu menerima email ini, konfigurasi SMTP sudah benar.',
                function ($message) use ($email) {
                    $message->to($email)->subject('Tes Email - Konfigurasi SMTP');
                }
            );

            $this->info('Email berhasil terkirim! Cek inbox ' . $email . ' (jangan lupa folder spam).');
            $this->line('Lihat log kirim di https://mailtrap.io/sending/email_logs');
        } catch (\Exception $e) {
            $this->error('Gagal mengirim email: ' . $e->getMessage());
            $this->line('Kemungkinan penyebab:');
            $this->line('  1. Domain belum terverifikasi di Mailtrap.');
            $this->line('  2. MAIL_PASSWORD belum diisi API token Mailtrap yang benar.');
            $this->line('  3. Port 587 atau koneksi SMTP diblokir firewall/ISP.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
