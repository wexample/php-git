<?php

namespace Wexample\PhpGit\Interface;

use Symfony\Component\Process\Process;

/**
 * How a git command is started in a repository: by whom, with what around it.
 *
 * git itself is the same everywhere; who runs it is not. A process writing as
 * root over a mounted repository has to run git as the repository's owner, or
 * the objects it leaves in `.git` are ones the owner's own git can no longer
 * write next to — and that is the environment's to know, not git's.
 */
interface GitProcessFactoryInterface
{
    /**
     * @param list<string>          $arguments what follows `git`
     * @param array<string, string> $env
     */
    public function create(
        string $repository,
        array $arguments,
        array $env = [],
    ): Process;
}
