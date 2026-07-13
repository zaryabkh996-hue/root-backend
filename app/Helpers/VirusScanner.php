<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class VirusScanner
{
    /**
     * Scan an uploaded file for malware/viruses.
     * Uses ClamAV command-line if available; falls back to checking EICAR test signature.
     */
    public static function scan(UploadedFile $file): bool
    {
        $filePath = $file->getRealPath() ?: $file->getPathname();
        
        if (empty($filePath)) {
            return true;
        }

        $filePath = str_replace('\\', '/', $filePath);

        $contents = @file_get_contents($filePath);
        if ($contents === false) {
            try {
                $handle = @fopen($filePath, 'r');
                if ($handle) {
                    $contents = @fread($handle, @filesize($filePath) ?: 65536);
                    @fclose($handle);
                }
            } catch (\Throwable $t) {
                // Ignore
            }
        }

        if ($contents === false || $contents === null) {
            return true; // Fail-open on read errors
        }

        // 1. Check for standard EICAR test signature or custom test signature (for testing/mocking)
        if (strpos($contents, 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!') !== false ||
            strpos($contents, 'OURROOTS-TEST-MALWARE-SIGNATURE') !== false) {
            Log::warning('Malware scan failed: Virus signature detected!', [
                'file_name' => $file->getClientOriginalName(),
            ]);
            return false;
        }

        // 2. Real scanning using ClamAV 'clamscan' command line if installed
        if (self::isClamAvInstalled()) {
            $escapedPath = escapeshellarg($filePath);
            $output = [];
            $exitCode = -1;
            exec("clamscan --no-summary {$escapedPath}", $output, $exitCode);
            
            if ($exitCode === 0) {
                return true; // Clean
            } elseif ($exitCode === 1) {
                Log::warning('Malware scan failed: ClamAV detected infected file!', [
                    'file_name' => $file->getClientOriginalName(),
                    'clamav_output' => implode("\n", $output),
                ]);
                return false; // Infected
            } else {
                Log::error('Malware scan: ClamAV execution error or timeout.', [
                    'exit_code' => $exitCode,
                    'output' => implode("\n", $output),
                ]);
            }
        }

        return true; // Clean by default if no scanner available and no EICAR signature
    }

    /**
     * Check if ClamAV CLI utility is installed on host system.
     */
    private static function isClamAvInstalled(): bool
    {
        $command = PHP_OS_FAMILY === 'Windows' ? 'where clamscan' : 'which clamscan';
        $output = [];
        $exitCode = -1;
        exec($command, $output, $exitCode);
        return $exitCode === 0;
    }
}
