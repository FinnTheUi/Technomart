<?php

namespace App\Console\Commands;

use App\Services\Analytics\TenantRiskAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class TrainRiskModelCommand extends Command
{
    protected $signature = 'ml:train';

    protected $description = 'Retrain the late-payment risk model on real payment history';

    public function handle(TenantRiskAnalyzer $analyzer): int
    {
        $rows = $analyzer->trainingRows();
        $minRows = config('services.ml.min_training_rows');

        if ($rows->count() < $minRows) {
            $this->components->warn("Only {$rows->count()} settled bills; need {$minRows} to retrain. Keeping the current model.");

            return self::SUCCESS;
        }

        $csvPath = storage_path('app/ml/training.csv');
        File::ensureDirectoryExists(dirname($csvPath));

        $handle = fopen($csvPath, 'w');
        fputcsv($handle, array_keys($rows->first()));
        $rows->each(fn ($row) => fputcsv($handle, $row));
        fclose($handle);

        $process = new Process([
            config('services.ml.python'), base_path('ml/train_model.py'),
            '--input', $csvPath,
            '--output', base_path('ml/risk_model.pkl'),
            '--min-rows', (string) $minRows,
            '--metrics-output', storage_path('app/ml/model_metrics.json'),
        ], base_path(), null, null, 300);

        $process->run();

        $result = json_decode(trim($process->getOutput()), true) ?? ['raw' => $process->getOutput().$process->getErrorOutput()];
        Log::info('Risk model training finished.', $result);

        if (($result['trained'] ?? false) === true) {
            $this->components->info('Model retrained on '.$result['rows'].' bills'
                .(isset($result['accuracy']) ? " (hold-out accuracy {$result['accuracy']}, ROC AUC {$result['roc_auc']})" : '').'.');

            $this->call('ml:score');

            return self::SUCCESS;
        }

        $this->components->warn('Model not retrained: '.($result['reason'] ?? $result['error'] ?? 'see logs'));

        return $process->getExitCode() === 2 ? self::SUCCESS : self::FAILURE;
    }
}
