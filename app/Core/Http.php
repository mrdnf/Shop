<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal cURL client used by inventory providers and courier integrations.
 */
final class Http
{
    /** @return array{status: int, body: string, json: mixed} */
    public static function request(string $method, string $url, array $headers = [], ?string $body = null, int $timeout = 20): array
    {
        if (!preg_match('#^https?://#i', $url)) {
            throw new \InvalidArgumentException('Only http(s) URLs are allowed.');
        }
        $ch = curl_init($url);
        $guard = self::guardEnabled();
        if ($guard) {
            // Pin the resolved public IP so DNS rebinding cannot reach internal hosts.
            $host = (string) parse_url($url, PHP_URL_HOST);
            $port = (int) (parse_url($url, PHP_URL_PORT) ?: (str_starts_with(strtolower($url), 'https') ? 443 : 80));
            $ip = self::assertPublicHost($host);
            curl_setopt($ch, CURLOPT_RESOLVE, ["{$host}:{$port}:{$ip}"]);
        }
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = "{$name}: {$value}";
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => !$guard,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_USERAGENT => 'Matjar/' . APP_VERSION,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('HTTP request failed: ' . $error);
        }
        return ['status' => $status, 'body' => (string) $response, 'json' => json_decode((string) $response, true)];
    }

    /**
     * In multi-shop (SaaS) mode shop admins must not be able to make the server
     * call private/internal addresses. Disable with config 'allow_private_hosts'.
     */
    public static function guardEnabled(): bool
    {
        return Config::isMulti() && !Config::get('allow_private_hosts', false);
    }

    /** Resolve a host and make sure it is a public address. Returns the IP. */
    public static function assertPublicHost(string $host): string
    {
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new \RuntimeException("Host \"{$host}\" resolves to a private or reserved address, which is not allowed.");
        }
        return $ip;
    }

    /** Build auth headers from a provider config (auth_type: none|bearer|basic|header). */
    public static function authHeaders(array $config): array
    {
        $headers = ['Accept' => 'application/json'];
        $value = (string) ($config['auth_value'] ?? '');
        switch ($config['auth_type'] ?? 'none') {
            case 'bearer':
                $headers['Authorization'] = 'Bearer ' . $value;
                break;
            case 'basic':
                $headers['Authorization'] = 'Basic ' . base64_encode($value);
                break;
            case 'header':
                $headers[(string) ($config['auth_header'] ?: 'X-API-Key')] = $value;
                break;
        }
        foreach (preg_split('/\r?\n/', (string) ($config['extra_headers'] ?? '')) ?: [] as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $headers[trim($k)] = trim($v);
            }
        }
        return $headers;
    }
}
