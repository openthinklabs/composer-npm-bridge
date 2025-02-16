<?php

namespace Eloquent\Composer\NpmBridge;

use Composer\IO\IOInterface;
use Composer\Util\ProcessExecutor;
use Eloquent\Composer\NpmBridge\Exception\NpmCommandFailedException;
use Eloquent\Composer\NpmBridge\Exception\NpmNotFoundException;
use Symfony\Component\Process\ExecutableFinder;

/**
 * A simple client for performing NPM operations.
 */
class NpmClient
{
    const DEFAULT_TIMEOUT = 3000;

    /**
     * Create a new NPM client.
     *
     * @return self The newly created client.
     */
    public static function create()
    {
        return new self(new ProcessExecutor(), new ExecutableFinder());
    }

    public function setIo(IOInterface $io)
    {
        $this->processExecutor = new ProcessExecutor($io);
        return $this;
    }

    /**
     * Construct a new NPM client.
     *
     * @access private
     *
     * @param ProcessExecutor  $processExecutor      The process executor to use.
     * @param ExecutableFinder $executableFinder     The executable finder to use.
     * @param callable         $getcwd               The getcwd() implementation to use.
     * @param callable         $chdir                The chdir() implementation to use.
     * @param string           $processExecutorClass The ProcessExecutor implementation to use.
     */
    public function __construct(
        ProcessExecutor $processExecutor,
        ExecutableFinder $executableFinder,
        $getcwd = 'getcwd',
        $chdir = 'chdir',
        $processExecutorClass = ProcessExecutor::class
    ) {
        $this->processExecutor = $processExecutor;
        $this->executableFinder = $executableFinder;
        $this->getcwd = $getcwd;
        $this->chdir = $chdir;

        $this->isNpmPathChecked = false;
        $this->getTimeout = [$processExecutorClass, 'getTimeout'];
        $this->setTimeout = [$processExecutorClass, 'setTimeout'];
    }

    /**
     * Install NPM dependencies for the project at the supplied path.
     *
     * @param string|null $path      The path to the NPM project, or null to use the current working directory.
     * @param bool        $isDevMode True if dev dependencies should be included.
     * @param int|null    $timeout   The process timeout, in seconds.
     *
     * @throws NpmNotFoundException      If the npm executable cannot be located.
     * @throws NpmCommandFailedException If the operation fails.
     */
    public function install($path = null, $isDevMode = true, $timeout = null, $npmArguments = [])
    {
        if ($isDevMode) {
            $arguments = ['ci'];
        } else {
            $arguments = ['ci', '--production'];
        }

        $arguments = array_merge($arguments, $npmArguments);

        if ($timeout === null) {
            $timeout = self::DEFAULT_TIMEOUT;
        }

        $this->executeNpm($arguments, $path, $timeout);
    }

    /**
     * Check if the npm executable is available.
     *
     * @return bool True if available.
     */
    public function isAvailable()
    {
        return null !== $this->npmPath();
    }

    private function executeNpm($arguments, $workingDirectoryPath, $timeout)
    {
        $npmPath = $this->npmPath();

        if (null === $npmPath) {
            throw new NpmNotFoundException();
        }

        array_unshift($arguments, $npmPath);
        $command = implode(' ', array_map('escapeshellarg', $arguments));

        if (null !== $workingDirectoryPath) {
            $previousWorkingDirectoryPath = call_user_func($this->getcwd);
            call_user_func($this->chdir, $workingDirectoryPath);
        }

        $oldTimeout = call_user_func($this->getTimeout);
        call_user_func($this->setTimeout, $timeout);

        $this->processExecutor->execute('pwd');
        $exitCode = $this->processExecutor->execute($command);

        call_user_func($this->setTimeout, $oldTimeout);

        if (null !== $workingDirectoryPath) {
            call_user_func($this->chdir, $previousWorkingDirectoryPath);
        }

        if (0 !== $exitCode) {
            throw new NpmCommandFailedException($command);
        }
    }

    private function npmPath()
    {
        if (!$this->npmPathChecked) {
            $this->npmPath = $this->executableFinder->find('npm');
            $this->npmPathChecked = true;
        }

        return $this->npmPath;
    }

    private $processExecutor;
    private $executableFinder;
    private $getcwd;
    private $chdir;
    private $npmPath;
    private $npmPathChecked;
    private $getTimeout;
    private $setTimeout;
}
