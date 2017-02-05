<?php
/**
 * BugNerd
 * Created by PhpStorm.
 * File: DumpCommand.php
 * User: thomas
 * Date: 04.02.17
 * Time: 17:41
 */

namespace SN\BackupBundle\Command;


use SN\DeployBundle\Services\Version;
use SN\ToolboxBundle\Helper\CommandHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

class DumpCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName("sn:backup:dump")
            ->setDescription("Take a snapshot of your current application.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs         = new Filesystem();
        $configs    = $this->getContainer()->getParameter('sn_backup');
        $tempFolder = sprintf("%s/../var/sn_backup", $this->getContainer()->get('kernel')->getRootDir());
        $backupFile = sprintf("%s/../backup.json", $this->getContainer()->get('kernel')->getRootDir());

        // prepare backup folder
        $fs->remove($tempFolder);
        $fs->mkdir($tempFolder);

        // Get configs
        $databaseUser     = $configs["database"]["user"];
        $databaseHost     = $configs["database"]["host"];
        $databasePort     = $configs["database"]["port"];
        $databasePassword = $configs["database"]["password"];
        $databaseName     = $configs["database"]["dbname"];
        $backupFolder     = $configs["backup_folder"];

        if ($databasePort == null) {
            $databasePort = 3306;
        }

        $cmd = sprintf("mysqldump -h %s -u %s -P %s --password='%s' --compress %s > %s/database.sql",
            $databaseHost,
            $databaseUser,
            $databasePort,
            $databasePassword,
            $databaseName,
            $tempFolder
        );

        CommandHelper::executeCommand($cmd, $output, false);

        try {
            $gaufrette = $this->getContainer()->get('knp_gaufrette.filesystem_map');

            foreach ($gaufrette as $folder => $gfs) {
                $files = $gfs->keys();
                foreach ($files as $file) {
                    $data = $gfs->read($file);
                    $fs->dumpFile(
                        sprintf("%s/%s/%s",
                            $tempFolder,
                            $folder,
                            $file),
                        $data);
                }
            }

        } catch (ServiceNotFoundException $exception) {
            $output->writeln("No Gaufrette-FilesystemMap found!");
        }

        $timestamp = time();
        CommandHelper::executeCommand(
            sprintf("cd %s; tar -czf %s/%s.tar.gz *",
                $tempFolder,
                $backupFolder,
                date("Y-m-d_H-i-s", $timestamp)),
            $output,
            false);
        $fs->remove($tempFolder);

        $commit  = null;
        $version = null;

        try {
            /**
             * @var $sn_deploy Version
             */
            $sn_deploy = $this->getContainer()->get('sn_deploy.twig');
            $commit    = $sn_deploy->getCommit();
            $version   = $sn_deploy->getVersion();
        } catch (ServiceNotFoundException $exception) {

        }

        if (file_exists($backupFile) === true) {
            $backupConfig = json_decode(file_get_contents($backupFile), true);
        } else {
            $backupConfig = ["dumps" => array()];
        }

        $dump = [
            "timestamp" => $timestamp,
            "commit"    => $commit,
            "version"   => $version
        ];

        array_unshift($backupConfig["dumps"], $dump);

        $fs->dumpFile($backupFile, json_encode($backupConfig));
    }
}