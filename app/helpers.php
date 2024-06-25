<?php

declare(strict_types=1);

function ssh_keys_exist(): bool
{
    return file_exists(config('app.ssh.private_key')) && file_exists(config('app.ssh.public_key'));
}

function get_ssh_public_key(): string
{
    return file_get_contents(config('app.ssh.public_key'));
}

function get_ssh_private_key(): string
{
    return file_get_contents(config('app.ssh.private_key'));
}

/**
 * Determines whether the 'GitHub' connection has been configured.
 *
 * @return bool
 */
function can_connect_github(): bool
{
    return config('services.github.client_id') || config('services.github.client_secret');
}

/**
 * Format timezones into a user-friendly format.
 *
 * @return array<string, string> Formatted timezones with keys as timezone identifiers and values as formatted strings.
 *
 * @throws Exception
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
