<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Check if SSH keys exist.
 *
 * This function checks for the existence of both private and public SSH key files.
 *
 * @return bool True if both private and public SSH keys exist, false otherwise.
 */
function ssh_keys_exist(): bool
{
    return file_exists(config('app.ssh.private_key'))
        && file_exists(config('app.ssh.public_key'));
}

/**
 * Get the contents of the SSH public key.
 *
 * @deprecated Please use the ServerConnectionManager static implementation.
 *
 * @return string The contents of the SSH public key.
 *
 * @throws RuntimeException If the public key file cannot be read.
 */
function get_ssh_public_key(): string
{
    $publicKeyPath = config('app.ssh.public_key');
    $publicKey = @file_get_contents($publicKeyPath);

    if ($publicKey === false) {
        throw new RuntimeException('Unable to read SSH public key from: ' . $publicKeyPath);
    }

    return $publicKey;
}

/**
 * Get the contents of the SSH private key.
 *
 * @deprecated Please use the ServerConnectionManager static implementation.
 *
 * @return string The contents of the SSH private key.
 *
 * @throws RuntimeException If the private key file cannot be read.
 */
function get_ssh_private_key(): string
{
    $privateKeyPath = config('app.ssh.private_key');
    $privateKey = @file_get_contents($privateKeyPath);

    if ($privateKey === false) {
        throw new RuntimeException('Unable to read SSH private key from: ' . $privateKeyPath);
    }

    return $privateKey;
}

/**
 * Format timezones into a user-friendly format.
 *
 * This function creates an array of formatted timezone strings, including GMT offset,
 * city name, and region.
 *
 * @return array<string, string> Formatted timezones with keys as timezone identifiers and values as formatted strings.
 *
 * @throws Exception If there's an error creating DateTime objects.
 */
function formatTimezones(): array
{
    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
    $formattedTimezones = [];

    foreach ($timezones as $timezone) {
        $dateTime = new DateTime('now', new DateTimeZone($timezone));
        $region = explode('/', $timezone)[0];
        $city = explode('/', $timezone)[1] ?? '';
        $city = str_replace('_', ' ', $city);
        $formattedTimezones[$timezone] = '(GMT ' . $dateTime->format('P') . ') ' . $city . ' (' . $region . ')';
    }

    return $formattedTimezones;
}

/**
 * Obtain the Vanguard version.
 *
 * This function reads the version from a file and caches it for a day.
 * If the version file doesn't exist, it returns 'Unknown'.
 *
 * @return string The Vanguard version or 'Unknown' if the version file is not found.
 */
function obtain_vanguard_version(): string
{
    $versionFile = base_path('VERSION');

    return Cache::remember('vanguard_version', now()->addDay(), static function () use ($versionFile): string {
        if (! File::exists($versionFile)) {
            return 'Unknown';
        }

        return trim(File::get($versionFile));
    });
}
