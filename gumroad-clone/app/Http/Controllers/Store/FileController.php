<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Order;
use App\Services\SecureFileServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FileController extends Controller
{
    private SecureFileServer $fileServer;

    public function __construct(SecureFileServer $fileServer)
    {
        $this->fileServer = $fileServer;
    }

    public function download(Request $request, Order $order, int $fileIndex)
    {
        // Verify the customer owns this order
        if (!$this->canAccessFile($request, $order)) {
            abort(403);
        }

        try {
            return $this->fileServer->serveFile($order, $fileIndex);
        } catch (\RuntimeException $e) {
            abort(404);
        }
    }

    private function canAccessFile(Request $request, Order $order): bool
    {
        // Check if user is store owner
        if (auth()->check() && auth()->id() === $order->product->storeContainer->silo->user_id) {
            return true;
        }

        // Verify customer email matches order
        $customerEmail = $request->get('email');
        if (!$customerEmail || $customerEmail !== $order->customer_details['email']) {
            return false;
        }

        // Verify download token
        $token = $request->get('token');
        if (!$token || !$this->validateDownloadToken($token, $order)) {
            return false;
        }

        return true;
    }

    private function validateDownloadToken(string $token, Order $order): bool
    {
        // Implement token validation
        return true;
    }
}
