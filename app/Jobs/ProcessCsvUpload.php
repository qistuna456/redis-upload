<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Bus\Queueable as BusQueueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\Upload;
use App\Models\Product;
use Carbon\Carbon;

class ProcessCsvUpload implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels, BusQueueable;
    
    public int $uploadId;
    
    public $timeout = 120;
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(int $uploadId)
    {
        $this->uploadId = $uploadId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $upload = Upload::find($this->uploadId);
        if (!$upload) {
            return;
        }
        
        $upload->update(['status' => 'processing']);
        
        $path = $upload->storage_path;
        $absolutePath = Storage::path($path);
        
        $total = 0;
        $processed = 0;
        $failed = 0;
        
        if (!is_readable($absolutePath)) {
            $upload->update([
                'status' => 'failed',
                'error_message' => 'Unable to read uploaded file.',
                'completed_at' => Carbon::now(),
            ]);
            return;
        }
        
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            $upload->update([
                'status' => 'failed',
                'error_message' => 'Unable to open uploaded file.',
                'completed_at' => Carbon::now(),
            ]);
            return;
        }
        
        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $upload->update([
                'status' => 'failed',
                'error_message' => 'CSV appears empty.',
                'completed_at' => Carbon::now(),
            ]);
            return;
        }
        
        // Normalize header keys
        $header = array_map(function ($h) {
            $h = $h ?? '';
            $h = is_string($h) ? $h : '';
            $h = trim($h);
            $h = preg_replace('/[\\x00-\\x1F\\x80-\\xFF]/u', '', $h); // strip non-utf8 control chars
            return strtoupper($h);
        }, $header);
        
        // Expected columns
        $expected = [
            'UNIQUE_KEY',
            'PRODUCT_TITLE',
            'PRODUCT_DESCRIPTION',
            'STYLE#',
            'SANMAR_MAINFRAME_COLOR',
            'SIZE',
            'COLOR_NAME',
            'PIECE_PRICE',
        ];
        
        // Map column indexes
        $index = [];
        foreach ($expected as $col) {
            $pos = array_search($col, $header, true);
            $index[$col] = $pos === false ? null : $pos;
        }
        
        // Process rows
        while (($row = fgetcsv($handle)) !== false) {
            $total++;
            // Clean each cell to UTF-8 printable
            $clean = array_map(function ($v) {
                if ($v === null) return null;
                if (!is_string($v)) $v = (string)$v;
                // Convert to UTF-8 and remove invalid bytes
                $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                $v = preg_replace('/[\\x00-\\x1F\\x80-\\xFF]/u', '', $v);
                return trim($v);
            }, $row);
            
            $uniqueKey = $this->getValue($clean, $index['UNIQUE_KEY']);
            if ($uniqueKey === null || $uniqueKey === '') {
                $failed++;
                continue;
            }
            
            try {
                Product::updateOrCreate(
                    ['unique_key' => $uniqueKey],
                    [
                        'product_title' => (string) $this->getValue($clean, $index['PRODUCT_TITLE']),
                        'product_description' => $this->nullableString($this->getValue($clean, $index['PRODUCT_DESCRIPTION'])),
                        'style_number' => $this->nullableString($this->getValue($clean, $index['STYLE#'])),
                        'sanmar_mainframe_color' => $this->nullableString($this->getValue($clean, $index['SANMAR_MAINFRAME_COLOR'])),
                        'size' => $this->nullableString($this->getValue($clean, $index['SIZE'])),
                        'color_name' => $this->nullableString($this->getValue($clean, $index['COLOR_NAME'])),
                        'piece_price' => $this->nullablePrice($this->getValue($clean, $index['PIECE_PRICE'])),
                    ]
                );
                $processed++;
            } catch (\Throwable $e) {
                $failed++;
            }
            
            if (($total % 50) === 0) {
                $upload->update([
                    'total_rows' => $total,
                    'processed_rows' => $processed,
                    'failed_rows' => $failed,
                ]);
            }
        }
        
        fclose($handle);
        
        $upload->update([
            'status' => 'completed',
            'total_rows' => $total,
            'processed_rows' => $processed,
            'failed_rows' => $failed,
            'completed_at' => Carbon::now(),
        ]);
    }
    
    private function getValue(array $row, ?int $idx): ?string
    {
        if ($idx === null) return null;
        return array_key_exists($idx, $row) ? $row[$idx] : null;
    }
    
    private function nullableString(?string $v): ?string
    {
        $v = $v === null ? null : trim($v);
        return $v === '' ? null : $v;
    }
    
    private function nullablePrice(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        if ($v === '') return null;
        // Remove currency symbols/commas
        $v = preg_replace('/[^0-9.\\-]/', '', $v);
        if ($v === '' || !is_numeric($v)) return null;
        // format with 2 decimals
        return number_format((float)$v, 2, '.', '');
    }
    }

