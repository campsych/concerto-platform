<?php

namespace Concerto\PanelBundle\Service;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class GitService
{
    private $adminService;
    private $kernel;

    public function __construct(KernelInterface $kernel, AdministrationService $adminService)
    {
        $this->kernel = $kernel;
        $this->adminService = $adminService;
    }

    public function enableGit($url, $branch, $login, $password, &$output)
    {
        $this->saveGitSettings($url, $branch, $login, $password);

        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $command = $app->find("concerto:git:enable");
        $arguments = ["command" => $command->getName()];
        $in = new ArrayInput($arguments);
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        $output = $out->fetch();
        if ($returnCode === 0) {
            return true;
        }

        $this->disableGit();
        return false;
    }

    public function getGitExecPath()
    {
        return $this->adminService->getSettingValue("git_exec_path");
    }

    public function getGitRepoPath()
    {
        return realpath(__DIR__ . "/../Resources") . "/git";
    }

    public function isEnabled()
    {
        return $this->adminService->getSettingValue("git_enabled") == 1;
    }

    public function getUrl()
    {
        return $this->adminService->getSettingValue("git_url");
    }

    public function getLogin()
    {
        return $this->adminService->getSettingValue("git_login");
    }

    public function getPassword()
    {
        return $this->adminService->getSettingValue("git_password");
    }

    public function getBranch()
    {
        return $this->adminService->getSettingValue("git_branch");
    }

    private function saveGitSettings($url, $branch, $login, $password)
    {
        $this->adminService->setSettings(array(
            "content_repository" => "git",
            "git_enabled" => 1,
            "git_url" => $url,
            "git_branch" => $branch,
            "git_login" => $login,
            "git_password" => $password
        ), true);
    }

    public function disableGit()
    {
        $this->adminService->setSettings(array(
            "content_repository" => "url",
            "git_enabled" => 0
        ), true);
    }

    public function getBehindNum(&$errorMessages = null)
    {
        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $command = $app->find("concerto:git:position");
        $arguments = [
            "command" => $command->getName(),
            "direction" => "behind"
        ];
        $in = new ArrayInput($arguments);
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        $output = $out->fetch();
        if ($returnCode === 0) {
            return (int)$output;
        }
        $errorMessages = [$output];
        return false;
    }

    public function getAheadNum(&$errorMessages = null)
    {
        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $command = $app->find("concerto:git:position");
        $arguments = [
            "command" => $command->getName(),
            "direction" => "ahead"
        ];
        $in = new ArrayInput($arguments);
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        $output = $out->fetch();
        if ($returnCode === 0) {
            return (int)$output;
        }
        $errorMessages = [$output];
        return false;
    }

    public function getHistory(&$errorMessages = null)
    {
        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $command = $app->find("concerto:git:history");
        $arguments = [
            "command" => $command->getName()
        ];
        $in = new ArrayInput($arguments);
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        $output = $out->fetch();
        if ($returnCode === 0) {
            return $this->parseHistory($output);
        }
        $errorMessages = [$output];
        return false;
    }

    private function parseHistory($history)
    {
        $result = [];
        $lines = explode("\n", $history);
        foreach ($lines as $line) {
            if (trim($line) === "") continue;
            preg_match("/(.*) \|\|\| (.*) \|\|\| (.*) \|\|\| (.*) \|\|\| (.*)/", $line, $entries);
            array_push($result, [
                "sha" => $entries[1],
                "committer" => $entries[2],
                "timeAgo" => $entries[3],
                "subject" => $entries[4],
                "ref" => $entries[5]
            ]);
        }
        return $result;
    }

    public function getDiff($sha, &$errorMessages = null)
    {
        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $command = $app->find("concerto:git:diff");
        $arguments = [
            "command" => $command->getName(),
            "--sha" => $sha
        ];
        $in = new ArrayInput($arguments);
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        $output = $out->fetch();
        if ($returnCode === 0) {
            return $output;
        }
        $errorMessages = [$output];
        return false;
    }

    public function fetch(&$errorMessages = null)
    {
        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $command = $app->find("concerto:git:fetch");
        $arguments = ["command" => $command->getName()];
        $in = new ArrayInput($arguments);
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        if ($returnCode === 0) {
            return true;
        }
        $errorMessages = [$out->fetch()];
        return false;
    }

    private function refreshWorkingCopy($exportInstructions, &$errorMessages = null)
    {
        if ($exportInstructions === null) $exportInstructions = $this->adminService->getSettingValue("content_export_options");

        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $command = $app->find("concerto:content:export");
        $arguments = [
            "command" => $command->getName(),
            "output" => $this->getGitRepoPath(),
            "--instructions" => $exportInstructions,
            "--sc" => true,
            "-y" => true
        ];
        $in = new ArrayInput($arguments);
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        if ($returnCode === 0) {
            return true;
        }
        $errorMessages = [$out->fetch()];
        return false;
    }

    private function add(&$errorMessages = null)
    {
        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $command = $app->find("concerto:git:add");
        $arguments = ["command" => $command->getName()];
        $in = new ArrayInput($arguments);
        $out = new BufferedOutput();
        $returnCode = $app->run($in, $out);
        if ($returnCode === 0) {
            return true;
        }
        $errorMessages = [$out->fetch()];
        return false;
    }

    public function getStatus($exportInstructions, &$errorMessages = null)
    {
        if ($this->fetch($errorMessages) === false) {
            $errorMessages[] = "Git fetch failed";
            return false;
        }

        $behind = $this->getBehindNum($errorMessages);
        if ($behind === false) {
            $errorMessages[] = "Git behind num failed";
            return false;
        }

        $ahead = $this->getAheadNum($errorMessages);
        if ($ahead === false) {
            $errorMessages[] = "Git ahead num failed";
            return false;
        }

        $history = $this->getHistory($errorMessages);
        if ($history === false) {
            $errorMessages[] = "Git history failed";
            return false;
        }

        if ($this->refreshWorkingCopy($exportInstructions, $errorMessages) === false) {
            $errorMessages[] = "Working copy refresh failed";
            return false;
        }

        if ($this->add($errorMessages) === false) {
            $errorMessages[] = "Git add failed";
            return false;
        }

        $diff = $this->getDiff(null, $errorMessages);
        if ($diff === false) {
            $errorMessages[] = "Git diff failed";
            return false;
        }

        return $status = [
            "behind" => $behind,
            "ahead" => $ahead,
            "history" => $history,
            "diff" => $diff
        ];
    }
}