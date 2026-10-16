<?php

namespace Wexample\PhpGit\Tests\Unit\Class;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Wexample\PhpGit\Class\GitCommitter;

class GitCommitterTest extends TestCase
{
    private string $repository;

    protected function setUp(): void
    {
        $this->repository = sys_get_temp_dir().'/php-git-committer-'.bin2hex(random_bytes(4));
        mkdir($this->repository);
        $this->git(['init', '--quiet', '--initial-branch=main']);
        file_put_contents($this->repository.'/.gitignore', "ignored.txt\n");
        $this->git(['add', '.gitignore']);
        $this->git(['-c', 'user.name=Test', '-c', 'user.email=test@example.com', 'commit', '--quiet', '-m', 'init']);
    }

    protected function tearDown(): void
    {
        (new Process(['rm', '-rf', $this->repository]))->run();
    }

    public function testCommitsTheGivenPathsAndNothingElse(): void
    {
        file_put_contents($this->repository.'/mine.txt', "mine\n");
        file_put_contents($this->repository.'/theirs.txt', "theirs\n");
        file_put_contents($this->repository.'/ignored.txt', "ignored\n");

        $commits = (new GitCommitter())->commit(
            [$this->repository.'/mine.txt', $this->repository.'/ignored.txt'],
            'Add mine',
            fn () => 'Board <board@example.com>',
        );

        $this->assertSame([$this->repository], array_keys($commits));
        $this->assertSame("mine.txt\n", $this->gitOutput(['show', '--name-only', '--format=', 'HEAD']));
        $this->assertSame("Board <board@example.com>\n", $this->gitOutput(['log', '-1', '--format=%an <%ae>']));
        // What was not given is left as it was, untracked.
        $this->assertStringContainsString('?? theirs.txt', $this->gitOutput(['status', '--porcelain']));
    }

    public function testMakesNoCommitWhenNothingGivenChanged(): void
    {
        $this->assertSame([], (new GitCommitter())->commit([$this->repository.'/.gitignore'], 'Nothing'));
    }

    private function gitOutput(array $arguments): string
    {
        $process = new Process(['git', '-C', $this->repository, ...$arguments]);
        $process->mustRun();

        return $process->getOutput();
    }

    private function git(array $arguments): void
    {
        (new Process(['git', '-C', $this->repository, ...$arguments]))->mustRun();
    }
}
