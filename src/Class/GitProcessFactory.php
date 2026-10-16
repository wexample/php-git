<?php

namespace Wexample\PhpGit\Class;

use Symfony\Component\Process\Process;
use Wexample\PhpGit\Interface\GitProcessFactoryInterface;

/**
 * git run by whoever runs PHP, in the repository given.
 */
class GitProcessFactory implements GitProcessFactoryInterface
{
    public function create(
        string $repository,
        array $arguments,
        array $env = [],
    ): Process {
        return new Process(
            // A repository owned by someone else is refused otherwise, and a
            // container runs git as whoever runs PHP.
            ['git', '-c', 'safe.directory='.$repository, '-C', $repository, ...$arguments],
            null,
            $env
        );
    }
}
