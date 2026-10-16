<?php

namespace Wexample\PhpGit\Class;

use Symfony\Component\Process\Process;
use Wexample\PhpGit\Interface\GitProcessFactoryInterface;

/**
 * git run by whoever runs PHP, in the repository given. Running it as someone
 * else is extending `command()` and `env()`.
 */
class GitProcessFactory implements GitProcessFactoryInterface
{
    public function create(
        string $repository,
        array $arguments,
        array $env = [],
    ): Process {
        return new Process(
            $this->command($repository, $arguments),
            null,
            $this->env($repository, $env)
        );
    }

    /**
     * @param list<string> $arguments
     *
     * @return list<string>
     */
    protected function command(
        string $repository,
        array $arguments,
    ): array {
        // A repository owned by someone else is refused otherwise, and a
        // container runs git as whoever runs PHP.
        return ['git', '-c', 'safe.directory='.$repository, '-C', $repository, ...$arguments];
    }

    /**
     * @param array<string, string> $env
     *
     * @return array<string, string>
     */
    protected function env(
        string $repository,
        array $env,
    ): array {
        return $env;
    }
}
