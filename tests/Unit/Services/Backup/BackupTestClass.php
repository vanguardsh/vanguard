<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Backup;

use App\Services\Backup\Backup;
use App\Services\Backup\Contracts\SFTPInterface;

class BackupTestClass extends Backup
{
    public function publicValidateConfiguration(): void
    {
        $this->validateConfiguration();
    }

    public function publicCreateSFTP(string $host, int $port, int $timeout): SFTPInterface
    {
        return $this->createSFTP($host, $port, $timeout);
    }
}
