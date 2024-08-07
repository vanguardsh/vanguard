<?php

declare(strict_types=1);
use App\Exceptions\BackupTaskRuntimeException;
use App\Exceptions\BackupTaskZipException;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\RemoteServer;
use App\Services\Backup\Contracts\SFTPInterface;
use App\Services\Backup\Destinations\Contracts\BackupDestinationInterface;
use App\Services\Backup\Destinations\S3;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Config;
use Tests\Unit\Services\Backup\BackupTestClass;

beforeEach(function (): void {
    Event::fake();
    $this->backup = Mockery::mock(BackupTestClass::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $this->mockSftp = Mockery::mock(SFTPInterface::class);
    $this->backup->shouldReceive('get_ssh_private_key')->andReturn('mock_private_key_content');
});

afterEach(function (): void {
    Mockery::close();
});

it('validates configuration successfully', function (): void {
    Config::set('app.ssh.passphrase', 'test_passphrase');
    Config::set('app.env', 'testing');

    $this->backup->shouldReceive('ssh_keys_exist')->andReturn(true);

    expect(fn () => $this->backup->publicValidateConfiguration())->not->toThrow(BackupTaskRuntimeException::class);
});

it('throws exception when SSH passphrase is not set', function (): void {
    Config::set('app.ssh.passphrase', null);
    Config::set('app.env', 'production');

    $this->backup->shouldReceive('ssh_keys_exist')->andReturn(true);

    expect(fn () => $this->backup->publicValidateConfiguration())->toThrow(BackupTaskRuntimeException::class);
});

it('obtains backup task', function (): void {
    $backupTask = BackupTask::factory()->create();

    $obtainedTask = $this->backup->obtainBackupTask($backupTask->id);

    expect($obtainedTask->id)->toBe($backupTask->id);
});

it('records backup task log', function (): void {
    $backupTask = BackupTask::factory()->create();
    $logOutput = 'Test log output';

    $backupTaskLog = $this->backup->recordBackupTaskLog($backupTask->id, $logOutput);

    expect($backupTaskLog)->toBeInstanceOf(BackupTaskLog::class)
        ->and($backupTaskLog->backup_task_id)->toBe($backupTask->id)
        ->and($backupTaskLog->output)->toBe($logOutput);
});

it('updates backup task log output', function (): void {
    $backupTaskLog = BackupTaskLog::factory()->create();
    $newLogOutput = 'Updated log output';

    $this->backup->updateBackupTaskLogOutput($backupTaskLog, $newLogOutput);

    expect($backupTaskLog->fresh()->output)->toBe($newLogOutput);
});

it('updates backup task status', function (): void {
    $backupTask = BackupTask::factory()->create(['status' => 'ready']);
    $newStatus = 'ready';

    $this->backup->updateBackupTaskStatus($backupTask, $newStatus);

    $backupTask->refresh();
    expect($backupTask->status)->toBe($newStatus);
});

it('checks if path exists', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/source')->andReturn(['type' => 2]); // 2 for directory

    $result = $this->backup->checkPathExists($this->mockSftp, '/path/to/source');

    expect($result)->toBeTrue();
});

it('gets remote directory size', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with("du -sb '/path/to/source' | cut -f1")->andReturn('1024');

    $result = $this->backup->getRemoteDirectorySize($this->mockSftp, '/path/to/source');

    expect($result)->toBe(1024);
});

it('establishes SFTP connection', function (): void {
    test_create_keys();
    $remoteServer = RemoteServer::factory()->create([
        'ip_address' => '192.168.1.1',
        'port' => 22,
        'username' => 'testuser',
        'connectivity_status' => 'offline',
    ]);

    $backupTask = BackupTask::factory()->create([
        'remote_server_id' => $remoteServer->id,
    ]);

    $mockSftp = Mockery::mock(SFTPInterface::class);
    $mockSftp->shouldReceive('login')->andReturn(true);

    $this->backup->shouldReceive('createSFTP')->andReturn($mockSftp);
    $this->backup->shouldReceive('get_ssh_private_key')->andReturn('mock_private_key');

    Mockery::mock('alias:phpseclib3\Crypt\PublicKeyLoader')
        ->shouldReceive('load')
        ->andReturn(Mockery::mock('phpseclib3\Crypt\Common\PrivateKey'));

    $sftp = $this->backup->establishSFTPConnection($backupTask);

    expect($sftp)->toBe($mockSftp)
        ->and($remoteServer->fresh()->connectivity_status)->toBe('online');
    test_restore_keys();
});

it('zips remote directory successfully', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('1024');
    $this->mockSftp->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('5000000');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern("/^cd '\/path\/to\/source' && zip -rv '\/tmp\/backup\.zip' \./"))
        ->andReturn('adding: somefile (stored 0%)');
    $this->mockSftp->shouldReceive('exec')->with("test -f '/tmp/backup.zip' && stat -c%s '/tmp/backup.zip'")->andReturn('512');

    $this->backup->zipRemoteDirectory($this->mockSftp, '/path/to/source', '/tmp/backup.zip', []);

    expect(true)->toBeTrue();
});

it('throws exception when zipping fails', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('1024');
    $this->mockSftp->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('5000000');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern("/^cd '\/path\/to\/source' && zip -rv '\/tmp\/backup\.zip' \./"))
        ->andReturn('zip error: Command failed');
    $this->mockSftp->shouldReceive('exec')->with("test -f '/tmp/backup.zip' && stat -c%s '/tmp/backup.zip'")->andReturn('0');

    expect(fn () => $this->backup->zipRemoteDirectory($this->mockSftp, '/path/to/source', '/tmp/backup.zip', []))
        ->toThrow(BackupTaskZipException::class);
});

it('throws exception when zipping returns an error message', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('du --version')->andReturn('du (GNU coreutils) 8.32');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^du -sb/'))->andReturn('1024');
    $this->mockSftp->shouldReceive('exec')->with("df -P '/tmp' | tail -1 | awk '{print $4}'")->andReturn('5000000');

    $this->mockSftp->shouldReceive('exec')
        ->with(Mockery::pattern("/^cd '\/path\/to\/source' && zip -rv '\/tmp\/backup\.zip' \./"))
        ->andReturn('zip error: Command failed');

    $this->backup->shouldReceive('retryCommand')
        ->once()
        ->andReturn('zip error: Command failed');

    expect(fn () => $this->backup->zipRemoteDirectory($this->mockSftp, '/path/to/source', '/tmp/backup.zip', []))
        ->toThrow(BackupTaskZipException::class, 'Failed to zip the directory after multiple attempts: zip error: Command failed');
});

it('gets database type', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with('mysql --version 2>&1')->andReturn('mysql  Ver 8.0.26');

    $dbType = $this->backup->getDatabaseType($this->mockSftp);

    expect($dbType)->toBe('mysql');
});

it('dumps remote database', function (): void {
    $this->mockSftp->shouldReceive('isConnected')->andReturn(true);
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^mysqldump/'))->andReturn('');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^test -s/'))->andReturn('exists');
    $this->mockSftp->shouldReceive('exec')->with(Mockery::pattern('/^cat/'))->andReturn('dump content');

    $this->backup->dumpRemoteDatabase(
        $this->mockSftp,
        'mysql',
        '/path/to/dump.sql',
        'password',
        'testdb',
        null
    );

    expect(true)->toBeTrue();
});

it('checks if directory is a Laravel project', function (): void {
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/artisan')->andReturn(['type' => 1]);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/composer.json')->andReturn(['type' => 1]);
    $this->mockSftp->shouldReceive('stat')->with('/path/to/laravel/package.json')->andReturn(['type' => 1]);

    $isLaravel = $this->backup->isLaravelDirectory($this->mockSftp, '/path/to/laravel');

    expect($isLaravel)->toBeTrue();
});

it('deletes folder', function (): void {
    $this->mockSftp->shouldReceive('exec')->with("rm -rf '/path/to/delete'")->andReturn('');

    $this->backup->deleteFolder($this->mockSftp, '/path/to/delete');

    expect(true)->toBeTrue();
});

it('creates backup destination instance', function (): void {
    $mock = Mockery::mock(BackupDestination::class);
    $mock->shouldReceive('getAttribute')->with('type')->andReturn('s3');
    $mock->shouldReceive('getAttribute')->with('s3_bucket_name')->andReturn('test-bucket');
    $mock->shouldReceive('getS3Client')->andReturn(Mockery::mock(S3Client::class));

    $s3Mock = Mockery::mock(S3::class, [Mockery::mock(S3Client::class), 'test-bucket']);
    $s3Mock->shouldReceive('listFiles')->andReturn([]);
    $s3Mock->shouldReceive('deleteFile')->andReturn(null);
    $s3Mock->shouldReceive('streamFiles')->andReturn(true);

    $instance = $this->backup->createBackupDestinationInstance($mock);

    expect($instance)->toBeInstanceOf(BackupDestinationInterface::class);
});
