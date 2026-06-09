<?php

declare(strict_types=1);

namespace App\Service;

final readonly class UrlSafety
{
    /**
     * @param array<string> $allowedSchemes
     * @param array<string> $deniedCidrs
     */
    public function __construct(
        private array $allowedSchemes = ['https'],
        private array $deniedCidrs = [
            '127.0.0.0/8',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '169.254.0.0/16',
            '0.0.0.0/8',
            '::1/128',
            'fc00::/7',
            'fe80::/10',
            '::/128',
        ],
    ) {
    }

    public function isSafe(string $url): bool
    {
        $parsed = parse_url($url);

        $scheme = $parsed['scheme'] ?? '';
        if (!in_array(strtolower($scheme), $this->allowedSchemes, true)) {
            return false;
        }

        $host = $parsed['host'] ?? '';
        if ('' === $host) {
            return false;
        }

        // Strip IPv6 bracket notation from parse_url (e.g. '[::1]' → '::1')
        $host = trim($host, '[]');

        // Check if host is already a direct IP address (no DNS resolution needed)
        $resolvedIp = filter_var($host, FILTER_VALIDATE_IP);
        if (false !== $resolvedIp) {
            $ips = [$resolvedIp];
        } else {
            $ips = gethostbynamel($host);
            if (false === $ips || [] === $ips) {
                return true;
            }
        }

        foreach ($ips as $ip) {
            $ipBinary = inet_pton($ip);
            if (false === $ipBinary) {
                return false;
            }

            foreach ($this->deniedCidrs as $cidr) {
                if ($this->ipInCidr($ip, $ipBinary, $cidr)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function ipInCidr(string $ip, string $ipBinary, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;
        $subnetBinary = inet_pton($subnet);

        if (false === $subnetBinary) {
            return false;
        }

        if (strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $fullMask = str_repeat("\xff", (int) floor($bits / 8));
        $remaining = $bits % 8;
        if (0 !== $remaining) {
            $fullMask .= chr((0xFF << (8 - $remaining)) & 0xFF);
        }
        $fullMask = str_pad($fullMask, strlen($ipBinary), "\x00");

        return ($ipBinary & $fullMask) === ($subnetBinary & $fullMask);
    }
}
