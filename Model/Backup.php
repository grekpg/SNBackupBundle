<?php
namespace SN\BackupBundle\Model;

use SN\ToolboxBundle\Helper\CommandHelper;
use Symfony\Component\Filesystem\Filesystem;

/**
 * SNBundle
 * Created by PhpStorm.
 * File: Backup.php
 * User: thomas
 * Date: 08.03.17
 * Time: 09:42
 */
class Backup implements \JsonSerializable
{
    protected $filename = null;
    protected $version;
    protected $timestamp;
    protected $commit;

    public function getFilename()
    {
        if ($this->filename == null) {
            return sprintf("%s.tar.gz", date("Y-m-d_H-i-s", $this->getTimestamp()));
        }

        return sprintf("%s.tar.gz", $this->filename);
    }

    public function archive_exists()
    {
        $fs = new Filesystem();

        return $fs->exists($this->getAbsolutepath());
    }

    /**
     * @param $fielname
     * @return $this
     */
    public function setFilename($fielname)
    {
        $this->filename = $fielname;

        return $this;
    }

    /**
     * @return \SplFileInfo|boolean
     */
    public function getFile()
    {
        $file = new \SplFileInfo($this->getAbsolutepath());

        if ($file->isFile() === false) {
            return false;
        }

        return $file;
    }

    /**
     * @return string
     */
    protected function getAbsolutepath()
    {
        return sprintf("%s/%s", $this->getFilepath(), $this->getFilename());
    }

    /**
     * @param \SplFileInfo $file
     */
    public function setFile(\SplFileInfo $file)
    {
        $fs = new Filesystem();
        $fs->dumpFile($this->getAbsolutepath(), file_get_contents($file->getRealPath()));
    }

    /**
     * @return string
     */
    protected function getFilepath()
    {
        if (Config::get(Config::GAUFRETTE)) {
            return sprintf("gaufrette://%s", Config::get(Config::BACKUP_FOLDER));
        }

        return Config::get(Config::BACKUP_FOLDER);
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $dstFolder
     */
    public function extractTo($dstFolder)
    {
        $tmpFile = sprintf("/tmp/%s.tar.gz", md5(time()));

        $fs = new Filesystem();
        $fs->copy($this->getAbsolutepath(), $tmpFile);

        $cmd = sprintf("tar xfz %s -C %s; rm -rf %s",
            $tmpFile,
            $dstFolder,
            $tmpFile
        );
        CommandHelper::executeCommand($cmd);
    }

    public function insertFrom($srcFolder)
    {
        $tmpFile = sprintf("/tmp/%s.tar.gz", md5(time()));

        $cmd = sprintf("cd %s; tar -czf %s *", $srcFolder, $tmpFile);
        CommandHelper::executeCommand($cmd);

        $fs = new Filesystem();
        $fs->copy($tmpFile, $this->getAbsolutepath(), true);
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param mixed $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return mixed
     */
    public function getCommit()
    {
        return $this->commit;
    }

    /**
     * @param mixed $commit
     */
    public function setCommit($commit)
    {
        $this->commit = $commit;
    }

    function jsonSerialize()
    {
        return [
            "timestamp" => $this->getTimestamp(),
            "version"   => $this->getVersion(),
            "commit"    => $this->getCommit()
        ];
    }

}