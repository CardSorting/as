<?php

namespace App\Services\DNS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DNSManager
{
    private $provider;
    private $zoneId;
    private $apiToken;
    private $baseDomain;

    public function __construct()
    {
        $this->provider = config('store-domains.dns.provider');
        $this->zoneId = config('store-domains.dns.zone_id');
        $this->apiToken = config('store-domains.dns.api_token');
        $this->baseDomain = config('store-domains.base_domain');
    }

    public function addStoreDomain(string $storeDomain): void
    {
        $subdomain = "{$storeDomain}.{$this->baseDomain}";
        
        match($this->provider) {
            'cloudflare' => $this->addCloudflareRecord($subdomain),
            default => throw new \RuntimeException("Unsupported DNS provider: {$this->provider}")
        };
    }

    public function addCustomDomain(string $customDomain, string $storeDomain): void
    {
        $target = "{$storeDomain}.{$this->baseDomain}";
        
        match($this->provider) {
            'cloudflare' => $this->addCloudflareCustomDomain($customDomain, $target),
            default => throw new \RuntimeException("Unsupported DNS provider: {$this->provider}")
        };
    }

    public function verifyDomain(string $domain, string $target): bool
    {
        $timeout = config('store-domains.dns.propagation_timeout');
        $interval = config('store-domains.dns.check_interval');
        $start = time();

        while (time() - $start < $timeout) {
            if ($this->checkDomain($domain, $target)) {
                return true;
            }
            sleep($interval);
        }

        return false;
    }

    private function addCloudflareRecord(string $subdomain): void
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
            'Content-Type' => 'application/json',
        ])->post("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records", [
            'type' => 'A',
            'name' => $subdomain,
            'content' => config('store-domains.app_ip'),
            'proxied' => true
        ]);

        if (!$response->successful()) {
            Log::error('Failed to add Cloudflare DNS record', [
                'domain' => $subdomain,
                'error' => $response->json()
            ]);
            throw new \RuntimeException('Failed to add DNS record');
        }
    }

    private function addCloudflareCustomDomain(string $domain, string $target): void
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
            'Content-Type' => 'application/json',
        ])->post("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records", [
            'type' => 'CNAME',
            'name' => $domain,
            'content' => $target,
            'proxied' => true
        ]);

        if (!$response->successful()) {
            Log::error('Failed to add Cloudflare CNAME record', [
                'domain' => $domain,
                'target' => $target,
                'error' => $response->json()
            ]);
            throw new \RuntimeException('Failed to add CNAME record');
        }
    }

    private function checkDomain(string $domain, string $target): bool
    {
        try {
            $records = dns_get_record($domain, DNS_CNAME);
            
            foreach ($records as $record) {
                if ($record['target'] === $target) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::warning('DNS check failed', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function removeDomain(string $domain): void
    {
        match($this->provider) {
            'cloudflare' => $this->removeCloudflareRecord($domain),
            default => throw new \RuntimeException("Unsupported DNS provider: {$this->provider}")
        };
    }

    private function removeCloudflareRecord(string $domain): void
    {
        // First, get the record ID
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records", [
            'name' => $domain
        ]);

        if (!$response->successful()) {
            Log::error('Failed to fetch Cloudflare DNS record', [
                'domain' => $domain,
                'error' => $response->json()
            ]);
            throw new \RuntimeException('Failed to fetch DNS record');
        }

        $records = $response->json()['result'];
        if (empty($records)) {
            return; // Record doesn't exist
        }

        // Delete the record
        $recordId = $records[0]['id'];
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
            'Content-Type' => 'application/json',
        ])->delete("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records/{$recordId}");

        if (!$response->successful()) {
            Log::error('Failed to remove Cloudflare DNS record', [
                'domain' => $domain,
                'error' => $response->json()
            ]);
            throw new \RuntimeException('Failed to remove DNS record');
        }
    }
}
