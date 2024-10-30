<?php

namespace App\Services;

use App\Models\Store\Order;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureFileServer
{
    private const DOWNLOAD_CHUNK_SIZE = 1024 * 1024; // 1MB chunks
    private const DOWNLOAD_TIMEOUT = 3600; // 1 hour

    public function serveFile(Order $order, int $fileIndex): StreamedResponse
    {
        $this->validateAccess($order, $fileIndex);

        $file = $order->product->files[$fileIndex];
        $path = $file['path'];

        return $this->streamFile(
            $path,
            $file['name'],
            $file['type'] ?? 'application/octet-stream'
        );
    }

    private function validateAccess(Order $order, int $fileIndex): void
    {
        if ($order->status !== 'paid') {
            throw new \RuntimeException('Order not paid');
        }

        if (!isset($order->product->files[$fileIndex])) {
            throw new \RuntimeException('File not found');
        }

        // Validate download count/expiry if implemented
        // $this->validateDownloadLimits($order);
    }

    private function streamFile(string $path, string $filename, string $mimeType): StreamedResponse
    {
        if (!Storage::disk('store_files')->exists($path)) {
            throw new \RuntimeException('File not found on disk');
        }

        $fullPath = Storage::disk('store_files')->path($path);
        $size = filesize($fullPath);

        return response()->stream(
            function () use ($fullPath) {
                $handle = fopen($fullPath, 'rb');
                
                while (!feof($handle)) {
                    echo fread($handle, self::DOWNLOAD_CHUNK_SIZE);
                    flush();
                }
                
                fclose($handle);
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => $size,
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }

    private function generateDownloadToken(Order $order, int $fileIndex): string
    {
        return hash_hmac(
            'sha256',
            "{$order->id}:{$fileIndex}:" . time(),
            config('app.key')
        );
    }

    private function validateDownloadToken(string $token, Order $order, int $fileIndex): bool
    {
        // Implement token validation logic
        return true;
    }
}
