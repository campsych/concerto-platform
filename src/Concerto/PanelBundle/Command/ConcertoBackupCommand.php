<?php

namespace Concerto\PanelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DateTime;

class ConcertoBackupCommand extends ContainerAwareCommand {

    const FILES_BACKUP_FILENAME = "c5_files_backup.zip";
    const DB_BACKUP_FILENAME = "c5_db_backup.sql";

    protected function configure() {
        $this->setName("concerto:backup")->setDescription("Backs up Concerto Platform.");
    }

    private function check(OutputInterface $output) {
        $output->writeln("checking...");

        if (strpos(strtolower(PHP_OS), "win") !== false) {
            $output->writeln("Windows OS is not supported by this command!");
            return false;
        }
        $doctrine = $this->getContainer()->get('doctrine');
        $upgrade_connection = $this->getContainer()->getParameter("administration")["upgrade_connection"];
        $connection = $doctrine->getConnection($upgrade_connection);
        if ($connection->getDriver()->getName() !== "pdo_mysql") {
            $output->writeln("only MySQL database driver is supported by this command!");
            return false;
        }
        $output->writeln("checks passed");
        return true;
    }

    private function getFileBackupPath() {
        return $this->getContainer()->getParameter("administration")["backup_directory"] . DIRECTORY_SEPARATOR . self::FILES_BACKUP_FILENAME;
    }

    private function getDatabaseBackupPath() {
        return $this->getContainer()->getParameter("administration")["backup_directory"] . DIRECTORY_SEPARATOR . self::DB_BACKUP_FILENAME;
    }

    private function backUpFiles(OutputInterface $output) {
        $output->writeln("backing up files...");
        $backup_path = $this->getFileBackupPath();
        $concerto_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..";
        $cmd = "zip -r $backup_path $concerto_path";
        system($cmd, $return_var);
        $success = $return_var === 0;
        if (!$success) {
            $output->writeln("files back up failed!");
            return false;
        }
        $output->writeln("files back up completed");
        return true;
    }

    private function backUpDatabase(OutputInterface $output) {
        $output->writeln("backing up database...");
        $backup_path = $this->getDatabaseBackupPath();

        $doctrine = $this->getContainer()->get('doctrine');
        $upgrade_connection = $this->getContainer()->getParameter("administration")["upgrade_connection"];
        $connection = $doctrine->getConnection($upgrade_connection);
        $user = $connection->getUsername();
        $pass = $connection->getPassword();
        $name = $connection->getDatabase();

        $cmd = "mysqldump -u$user -p$pass $name > $backup_path";
        system($cmd, $return_var);
        $success = $return_var === 0;
        if (!$success) {
            $output->writeln("database back up failed!");
            return false;
        }
        $output->writeln("database back up completed");
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if (!$this->check($output))
            return 1;

        if (!$this->backUpFiles($output)) {
            return 1;
        }
        if (!$this->backUpDatabase($output)) {
            return 1;
        }

        $service = $this->getContainer()->get("concerto_panel.Administration_service");
        $service->setBackupPlatformVersion($this->getContainer()->getParameter("version"));
        $service->setBackupPlatformPath($this->getFileBackupPath());
        $service->setBackupDatabasePath($this->getDatabaseBackupPath());
        $content_version = $service->getInstalledContentVersion();
        if ($content_version === null)
            $content_version = "";
        $service->setBackupContentVersion($content_version);
        $service->setBackupTime(new DateTime("now"));

        $output->writeln("backup completed successfuly");
    }

}
