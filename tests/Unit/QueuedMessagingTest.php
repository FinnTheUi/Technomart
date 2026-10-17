<?php

namespace Tests\Unit;

use Illuminate\Contracts\Queue\ShouldQueue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every notification and mailable must be queued so slow mail servers never block a request.
 */
class QueuedMessagingTest extends TestCase
{
    public static function messageClasses(): array
    {
        $classes = [];

        foreach (['Notifications' => 'App\\Notifications\\', 'Mail' => 'App\\Mail\\'] as $dir => $namespace) {
            foreach (glob(dirname(__DIR__, 2)."/app/{$dir}/*.php") as $file) {
                $class = $namespace.basename($file, '.php');
                $classes[$class] = [$class];
            }
        }

        return $classes;
    }

    #[DataProvider('messageClasses')]
    public function test_message_is_queued(string $class): void
    {
        $this->assertTrue(is_subclass_of($class, ShouldQueue::class), "{$class} should implement ShouldQueue.");
    }

    public function test_queued_jobs_wait_for_database_commits(): void
    {
        $this->assertTrue(config('queue.connections.database.after_commit'));
    }
}
