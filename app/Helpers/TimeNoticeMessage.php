<?php

namespace App\Helpers;

/**
 * Format pesan berbasis waktu yang menampilkan JAM + durasi relatif, supaya
 * pengguna langsung tahu kapan bisa bertindak lagi.
 *
 * Dipakai untuk:
 *   - retryAt()   : pesan rate limit (kapan bisa mencoba lagi)
 *   - validUntil(): pesan batas berlaku (mis. kode OTP)
 */
class TimeNoticeMessage
{
    /**
     * Pesan retry berisi jam (waktu server) + durasi relatif.
     *
     * @param  string  $actionPhrase  Kalimat pembuka tanpa tanda titik akhir.
     *                                Contoh: 'Terlalu banyak percobaan login'
     * @param  int     $seconds       Detik tersisa sampai bisa mencoba lagi
     */
    public static function retryAt(string $actionPhrase, int $seconds): string
    {
        return "{$actionPhrase}. Silakan coba lagi "
            . static::timeLabel($seconds)
            . ' (' . static::relativeDuration($seconds, 'dalam', roundUp: true) . ').';
    }

    /**
     * Pesan batas berlaku berisi jam berakhir + sisa durasi.
     *
     * @param  string  $actionPhrase  Kalimat pembuka tanpa tanda titik akhir.
     *                                Contoh: 'Kode OTP masih berlaku'
     * @param  int     $seconds       Detik tersisa sampai masa berlaku habis
     */
    public static function validUntil(string $actionPhrase, int $seconds): string
    {
        return "{$actionPhrase} hingga "
            . static::timeLabel($seconds)
            . ' (' . static::relativeDuration($seconds, 'sisa', roundUp: false) . ').';
    }

    /**
     * Label jam dengan ZONA TAMPILAN pengguna (bukan zona internal server),
     * plus penanganan lintas hari agar tidak membingungkan.
     *
     * Contoh: 'pukul 14:20', 'besok pukul 00:05', '25/08 pukul 07:30'.
     */
    private static function timeLabel(int $seconds): string
    {
        $at = now(config('app.display_timezone', 'Asia/Jakarta'))
            ->addSeconds($seconds);

        if ($at->isTomorrow()) {
            return 'besok pukul ' . $at->format('H:i');
        }
        if (!$at->isToday()) {
            return $at->format('d/m') . ' pukul ' . $at->format('H:i');
        }

        return 'pukul ' . $at->format('H:i');
    }

    /**
     * Durasi relatif: 'dalam 15 menit' / 'sisa 3 menit' / 'dalam 1 jam'.
     *
     * @param  bool  $roundUp  true → dibulatkan ke atas (konservatif untuk
     *                         "coba lagi dalam X"); false → dibulatkan ke bawah
     *                         agar tidak melebih-lebihkan sisa waktu.
     */
    private static function relativeDuration(int $seconds, string $prefix, bool $roundUp): string
    {
        $unit = $seconds >= 3600 ? 3600 : ($seconds >= 60 ? 60 : 1);

        if ($unit === 1) {
            $value = max(1, $seconds);
        } else {
            $value = $roundUp ? ceil($seconds / $unit) : max(1, floor($seconds / $unit));
        }

        $label = $unit >= 3600 ? 'jam' : ($unit >= 60 ? 'menit' : 'detik');

        return "{$prefix} {$value} {$label}";
    }
}
