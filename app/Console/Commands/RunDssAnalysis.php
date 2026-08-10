<?php

namespace App\Console\Commands;

use App\Models\Enclosure;
use App\Services\DssService;
use Illuminate\Console\Command;

/**
 * Artisan command untuk menjalankan analisis DSS.
 *
 * Kegunaan:
 *   php artisan dss:analyze           — Analisis semua enclosure aktif
 *   php artisan dss:analyze 1         — Analisis enclosure dengan ID = 1
 *   php artisan dss:analyze --hours=6 — Gunakan jendela analisis 6 jam
 *
 * Dapat dijadwalkan di routes/console.php (misalnya setiap jam).
 */
class RunDssAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dss:analyze
                            {enclosure_id? : ID enclosure yang akan dianalisis (opsional; semua aktif jika dikosongkan)}
                            {--hours=24    : Jendela waktu analisis dalam jam (1–168)}';

    /**
     * The console command description.
     */
    protected $description = 'Jalankan analisis DSS (Stability Score + Insight + Recommendation) untuk satu atau semua enclosure aktif.';

    public function __construct(private readonly DssService $dssService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $enclosureId = $this->argument('enclosure_id');
        $hours       = (int) $this->option('hours');

        // Validasi range jam
        if ($hours < 1 || $hours > 168) {
            $this->error("Nilai --hours harus antara 1–168. Diterima: {$hours}");
            return self::FAILURE;
        }

        // Tentukan enclosure yang akan dianalisis
        $enclosures = $enclosureId
            ? Enclosure::with('parameters')->where('id', $enclosureId)->where('is_active', true)->get()
            : Enclosure::with('parameters')->where('is_active', true)->get();

        if ($enclosures->isEmpty()) {
            $this->warn($enclosureId
                ? "Enclosure dengan ID {$enclosureId} tidak ditemukan atau tidak aktif."
                : "Tidak ada enclosure aktif yang ditemukan."
            );
            return self::FAILURE;
        }

        $this->info("🔬 Memulai analisis DSS untuk {$enclosures->count()} enclosure (jendela: {$hours} jam)...");
        $this->newLine();

        $successCount = 0;
        $failCount    = 0;

        foreach ($enclosures as $enclosure) {
            $this->line("  ➤ Enclosure #{$enclosure->id}: {$enclosure->name}");

            try {
                $result = $this->dssService->analyze($enclosure, $hours);

                if ($result['stability'] === null) {
                    // Tidak cukup data
                    $this->warn("    ⚠  {$result['message']}");
                    $failCount++;
                    continue;
                }

                $score  = $result['stability']['final_stability_score'];
                $status = $result['stability']['status'];
                $hasRec = $result['recommendation'] !== null ? '✓ Rekomendasi dibuat' : '– Tidak perlu rekomendasi';

                $this->info("    ✅ Skor: {$score}/100 ({$status}) | {$hasRec}");
                $successCount++;

            } catch (\Throwable $e) {
                $this->error("    ❌ Gagal: {$e->getMessage()}");
                report($e);
                $failCount++;
            }

            $this->newLine();
        }

        // ── Ringkasan ─────────────────────────────────────────────
        $this->line("─────────────────────────────────────────");
        $this->info("Analisis selesai: {$successCount} berhasil, {$failCount} gagal/dilewati.");

        return $failCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
