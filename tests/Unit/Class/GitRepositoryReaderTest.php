<?php

namespace Wexample\PhpGit\Tests\Unit\Class;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Wexample\PhpGit\Class\GitRepositoryReader;

class GitRepositoryReaderTest extends TestCase
{
    private string $repository;

    protected function setUp(): void
    {
        $this->repository = sys_get_temp_dir().'/php-git-reader-'.bin2hex(random_bytes(4));
        mkdir($this->repository);
        $this->git(['init', '--quiet', '--initial-branch=main']);
    }

    protected function tearDown(): void
    {
        (new Process(['rm', '-rf', $this->repository]))->run();
    }

    public function testCountsNothingInARepositoryWithoutCommit(): void
    {
        $this->assertSame(0, (new GitRepositoryReader())->count($this->repository));
    }

    public function testReadsTheHistoryPageAfterPage(): void
    {
        foreach (['one', 'two', 'three', 'four', 'five'] as $subject) {
            $this->commitFile($subject.'.txt', $subject);
        }

        $reader = new GitRepositoryReader();

        $this->assertSame(5, $reader->count($this->repository));
        $this->assertSame(
            ['five', 'four'],
            array_column($reader->log($this->repository, 2), 'subject')
        );
        $this->assertSame(
            ['three', 'two'],
            array_column($reader->log($this->repository, 2, skip: 2), 'subject')
        );
        $this->assertSame(
            ['one'],
            array_column($reader->log($this->repository, 2, skip: 4), 'subject')
        );
        $this->assertSame([], $reader->log($this->repository, 2, skip: 6));
    }

    public function testReadsTheHistoryOfOnePath(): void
    {
        $this->commitFile('a.txt', 'first a');
        $this->commitFile('b.txt', 'only b');
        $this->commitFile('a.txt', 'second a');

        $reader = new GitRepositoryReader();
        $log = $reader->log($this->repository, 10, 'a.txt');

        $this->assertSame(2, $reader->count($this->repository, 'a.txt'));
        $this->assertSame(['second a', 'first a'], array_column($log, 'subject'));
        // The commit on b.txt is skipped: the newer one hangs from the older.
        $this->assertSame([$log[1]['hash']], $log[0]['parents']);
    }

    public function testKnowsNothingOutsideARepository(): void
    {
        $reader = new GitRepositoryReader();

        $this->assertNull($reader->log(sys_get_temp_dir()));
        $this->assertNull($reader->count(sys_get_temp_dir()));
    }

    private function commitFile(string $name, string $subject): void
    {
        file_put_contents($this->repository.'/'.$name, $subject."\n", FILE_APPEND);
        $this->git(['add', $name]);
        $this->git(['-c', 'user.name=Test', '-c', 'user.email=test@example.com', 'commit', '--quiet', '-m', $subject]);
    }

    private function git(array $arguments): void
    {
        (new Process(['git', '-C', $this->repository, ...$arguments]))->mustRun();
    }
}
