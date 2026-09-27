<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GoogleSheetController extends Controller
{
    protected function ensureGoogleServicesAutoloaded(): void
    {
        if (class_exists(\Google\Service\Sheets::class)) {
            return;
        }

        $base = base_path('vendor/google/apiclient-services/src');
        if (!is_dir($base)) {
            throw new \Exception('google/apiclient-services is not installed.');
        }

        // Fallback autoloader: some environments ship vendor/ without generated classmap.
        // This maps Google\Service\* classes to vendor/google/apiclient-services/src/*
        spl_autoload_register(function (string $class) use ($base) {
            $prefix = 'Google\\Service\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix)); // e.g. Sheets\Resource\SpreadsheetsValues
            $path = $base . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

            if (is_file($path)) {
                require_once $path;
                return;
            }

            // Some classes live at the root of src (e.g. Sheets.php)
            $alt = $base . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
            if (is_file($alt)) {
                require_once $alt;
            }
        }, prepend: true);

        if (!class_exists(\Google\Service\Sheets::class)) {
            throw new \Exception('Failed to autoload Google\\Service\\Sheets. Please run Composer install/dump-autoload.');
        }
    }

    protected function getClient()
    {
        $this->ensureGoogleServicesAutoloaded();

        $client = new Client();
        $client->setApplicationName((string) config('app.name', 'nextlogistic'));
        $client->setScopes([Sheets::SPREADSHEETS]);

        // 🔥 FIX: sebagian instalasi XAMPP/Windows tidak punya CA bundle terpasang di
        // php.ini (curl.cainfo/openssl.cafile), yang bikin request ke oauth2.googleapis.com
        // gagal dengan "SSL certificate ... unable to get local issuer certificate".
        // Pakai CA bundle yang kita sertakan sendiri di storage supaya tidak bergantung
        // konfigurasi PHP di server/komputer masing-masing.
        $caBundlePath = storage_path('app/private/cacert.pem');
        if (is_file($caBundlePath)) {
            $client->setHttpClient(new GuzzleClient([
                'verify' => $caBundlePath,
            ]));
        }

        $credentials = $this->resolveGoogleCredentials();

        // 🔥 FIX PENTING: normalize private key (atasi error OpenSSL)
        if (isset($credentials['private_key'])) {
            $credentials['private_key'] = str_replace(
                ["\\n", "\n", "\r"],
                "\n",
                $credentials['private_key']
            );
        }

        // Debug opsional
        if (!openssl_pkey_get_private($credentials['private_key'])) {
            throw new \Exception('Private key invalid (OpenSSL failed).');
        }

        $client->setAuthConfig($credentials);

        return $client;
    }

    /**
     * Ambil kredensial service account Google, dengan urutan prioritas:
     * 1. Env var GOOGLE_SERVICE_ACCOUNT_JSON berisi JSON mentah (disarankan untuk
     *    deployment seperti Railway, supaya private key tidak perlu ikut di-commit ke git).
     * 2. Env var GOOGLE_SERVICE_ACCOUNT_JSON berisi JSON dalam bentuk base64.
     * 3. Env var GOOGLE_SERVICE_ACCOUNT_JSON berisi path ke file JSON (kompatibel dengan
     *    konfigurasi lama).
     * 4. File lokal storage/app/google/service-account.json (dev lokal / XAMPP).
     * 5. File lokal storage/app/google-service-account.json (fallback lama).
     */
    protected function resolveGoogleCredentials(): array
    {
        $raw = trim((string) env('GOOGLE_SERVICE_ACCOUNT_JSON', ''));

        if ($raw !== '') {
            // 1) JSON mentah langsung di env var
            if (str_starts_with($raw, '{')) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
                throw new \Exception('GOOGLE_SERVICE_ACCOUNT_JSON berisi JSON yang tidak valid.');
            }

            // 2) JSON dalam bentuk base64
            $maybeDecoded = base64_decode($raw, true);
            if ($maybeDecoded !== false) {
                $decoded = json_decode($maybeDecoded, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            // 3) Dianggap path ke file JSON
            if (is_file($raw)) {
                $decoded = json_decode((string) file_get_contents($raw), true);
                if (is_array($decoded)) {
                    return $decoded;
                }
                throw new \Exception("File kredensial Google di GOOGLE_SERVICE_ACCOUNT_JSON ({$raw}) berisi JSON yang tidak valid.");
            }
        }

        // 4) & 5) Fallback ke file lokal (dev/XAMPP)
        foreach ([
            storage_path('app/google/service-account.json'),
            storage_path('app/google-service-account.json'),
        ] as $localPath) {
            if (is_file($localPath)) {
                $decoded = json_decode((string) file_get_contents($localPath), true);
                if (is_array($decoded)) {
                    return $decoded;
                }
                throw new \Exception("File kredensial Google di {$localPath} berisi JSON yang tidak valid.");
            }
        }

        throw new \Exception(
            'Kredensial Google Service Account tidak ditemukan. Set environment variable ' .
            'GOOGLE_SERVICE_ACCOUNT_JSON (isi JSON service account-nya), atau taruh filenya ' .
            'di storage/app/google/service-account.json.'
        );
    }

    protected function getSheetsService()
    {
        $this->ensureGoogleServicesAutoloaded();
        return new Sheets($this->getClient());
    }

    /**
     * Sync approved transactions to Google Sheets
     */
    public function syncTransactions($transactions = null)
    {
        $service = $this->getSheetsService();
        $spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID');

        if (!$spreadsheetId) {
            throw new \Exception('Spreadsheet ID not configured.');
        }

        if ($transactions === null) {
            $transactions = Transaction::approved()
                ->with(['item', 'user', 'approver'])
                ->orderBy('date', 'asc')
                ->get();
        }

        if ($transactions instanceof Collection) {
            $transactions->load(['item', 'user', 'approver']);
        }

        $this->ensureDepartmentSheets($service, $spreadsheetId);

        foreach ($this->departmentSheetConfig() as $bidang => $config) {
            $rows = collect($transactions)
                ->where('bidang', $bidang)
                ->map(fn(Transaction $tx) => $this->mapTransactionRow($tx, $bidang))
                ->values()
                ->all();

            if ($rows === []) {
                continue;
            }

            $service->spreadsheets_values->append(
                $spreadsheetId,
                $this->quotedSheetRange($config['title'], $config['range']),
                new ValueRange(['values' => $rows]),
                ['valueInputOption' => 'USER_ENTERED']
            );
        }
    }

    /**
     * Keep Google Sheets in sync with MySQL (source of truth).
     * Clears sheet then writes header + all approved transactions.
     */
    public function syncAllApprovedToSheet(): int
    {
        $service = $this->getSheetsService();
        $spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID');

        if (!$spreadsheetId) {
            throw new \Exception('Spreadsheet ID not configured.');
        }

        $this->ensureDepartmentSheets($service, $spreadsheetId);

        foreach ($this->departmentSheetConfig() as $config) {
            $service->spreadsheets_values->clear(
                $spreadsheetId,
                $this->quotedSheetRange($config['title'], $config['range']),
                new \Google\Service\Sheets\ClearValuesRequest()
            );

            $service->spreadsheets_values->update(
                $spreadsheetId,
                $this->quotedSheetRange($config['title'], $config['headerRange']),
                new ValueRange(['values' => [$config['headers']]]),
                ['valueInputOption' => 'USER_ENTERED']
            );
        }

        $transactions = Transaction::approved()
            ->with(['item', 'user', 'approver'])
            ->orderBy('date', 'asc')
            ->get();

        $this->syncTransactions($transactions);

        // Hitung hanya transaksi yang benar-benar disinkronkan (bidang dalam config).
        $syncedBidang = array_keys($this->departmentSheetConfig());

        return $transactions->whereIn('bidang', $syncedBidang)->count();
    }

    private function ensureDepartmentSheets(Sheets $service, string $spreadsheetId): void
    {
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $sheets = collect($spreadsheet->getSheets() ?? []);
        $sheetByTitle = $sheets->mapWithKeys(function ($sheet) {
            $properties = $sheet->getProperties();
            return [$properties->getTitle() => $properties];
        });

        $requests = [];
        foreach ($this->departmentSheetConfig() as $config) {
            $title = $config['title'];
            $legacyTitle = $config['legacyTitle'];
            $index = $config['index'];

            if ($sheetByTitle->has($title)) {
                $requests[] = new \Google\Service\Sheets\Request([
                    'updateSheetProperties' => [
                        'properties' => [
                            'sheetId' => $sheetByTitle[$title]->getSheetId(),
                            'index' => $index,
                        ],
                        'fields' => 'index',
                    ],
                ]);
                continue;
            }

            if ($sheetByTitle->has($legacyTitle)) {
                $requests[] = new \Google\Service\Sheets\Request([
                    'updateSheetProperties' => [
                        'properties' => [
                            'sheetId' => $sheetByTitle[$legacyTitle]->getSheetId(),
                            'title' => $title,
                            'index' => $index,
                        ],
                        'fields' => 'title,index',
                    ],
                ]);
                continue;
            }

            $requests[] = new \Google\Service\Sheets\Request([
                'addSheet' => [
                    'properties' => [
                        'title' => $title,
                        'index' => $index,
                    ],
                ],
            ]);
        }

        if ($requests !== []) {
            $service->spreadsheets->batchUpdate(
                $spreadsheetId,
                new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                    'requests' => $requests,
                ])
            );
        }
    }

    private function departmentSheetConfig(): array
    {
        return [
            'umum' => [
                'title' => 'Umum',
                'legacyTitle' => 'Sheet1',
                'index' => 0,
                'range' => 'A:M',
                'headerRange' => 'A1:M1',
                'headers' => [
                    'ID',
                    'Tanggal',
                    'Nama Barang',
                    'Kategori',
                    'Jenis',
                    'Jumlah',
                    'Satuan',
                    'Harga Satuan',
                    'User',
                    'Keterangan',
                    'Status',
                    'Disetujui Oleh',
                    'Tanggal Approval',
                ],
            ],
            // Sheet Teknik sengaja dihapus dari sinkronisasi.
            // Hanya transaksi bidang Umum yang disinkronkan ke Google Sheets.
        ];
    }

    private function mapTransactionRow(Transaction $tx, string $bidang): array
    {
        // Hanya bidang 'umum' yang didukung; teknik tidak disinkronkan.
        return [
            $tx->id,
            $tx->date->format('d/m/Y'),
            $tx->item->name ?? '-',
            $tx->item->category ?? '-',
            strtoupper($tx->type),
            $tx->quantity,
            $tx->item->unit ?? '-',
            $this->blankIfNull($tx->price),
            $tx->user->name ?? '-',
            $tx->description ?? '-',
            strtoupper($tx->status),
            $tx->approver->name ?? '-',
            $tx->approved_at ? $tx->approved_at->format('d/m/Y H:i') : '-',
        ];
    }

    private function quotedSheetRange(string $sheetTitle, string $range): string
    {
        return "'" . str_replace("'", "''", $sheetTitle) . "'!" . $range;
    }

    private function blankIfNull($value)
    {
        return $value === null ? '' : $value;
    }

    /**
     * Full sync: Clear sheet and re-sync all approved transactions
     */
    public function fullSync()
    {
        try {
            $count = $this->syncAllApprovedToSheet();
            return back()->with('success', 'Berhasil sinkronisasi ' . $count . ' transaksi ke Google Sheets.');
        } catch (\Exception $e) {
            Log::error('Google Sheets fullSync failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }
}
