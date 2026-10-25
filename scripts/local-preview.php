<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__).'/vendor/autoload.php';

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Tests\Support\LocalPreview;

try {
    $action = $argv[1] ?? 'status';
    if ($action === 'setup') {
        LocalPreview::initialize();
    }
    $app = LocalPreview::boot();
    if ($action === 'setup') {
        LocalPreview::prepareDatabases($app);
        echo "Local preview ready. Two dedicated SQLite files; production configuration unchanged.\n";
    } elseif ($action === 'admin:create') {
        if (! filter_var($argv[2] ?? '', FILTER_VALIDATE_EMAIL)
            || array_diff(array_slice($argv, 3), ['--superadmin'])) {
            fwrite(STDERR, "Use admin:create <email> --superadmin and enter the password at the hidden prompt.\n");
            exit(1);
        }
        if (User::where('email', $argv[2] ?? '')->exists()) {
            fwrite(STDERR, "Preview account already exists; password and role were not changed.\n");
            exit(1);
        }
        $input = new ArgvInput(array_merge(['artisan'], array_slice($argv, 1)));
        exit($app->make(Kernel::class)->handle($input, new ConsoleOutput));
    } elseif ($action !== 'status') {
        throw new RuntimeException('Allowed commands: setup, status, admin:create.');
    }
    echo json_encode(['environment' => app()->environment(), 'loopback' => '127.0.0.1:8766',
        'database_root' => realpath(LocalPreview::directory()), 'website_driver' => 'sqlite', 'stories_driver' => 'sqlite',
        'website_database' => realpath(config('database.connections.sqlite.database')),
        'stories_database' => realpath(config('database.connections.stories.database')),
        'stories_migrations' => DB::connection('stories')->table('migrations')->pluck('migration'),
        'accounts' => User::query()->get(['email', 'role'])->toArray()], JSON_PRETTY_PRINT).PHP_EOL;

} catch (Throwable $exception) {
    fwrite(STDERR, 'Preview command failed ('.get_class($exception).'). No fallback database was used.'.PHP_EOL);
    exit(1);
}
