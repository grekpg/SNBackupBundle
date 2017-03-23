<?php
namespace SN\BackupBundle\Model;

use Gaufrette\File;
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

    const TYPE_DAILY   = 'daily';
    const TYPE_WEEKLY  = 'weekly';
    const TYPE_MONTHLY = 'monthly';
    const TYPE_YEARLY  = 'yearly';


    protected $filename = null;
    protected $version;
    protected $type = null;
    /**
     * @var \DateTime
     */
    protected $dateTime;
    protected $commit;

    public function __construct()
    {
        $this->dateTime = new \DateTime();
    }

    public function remove()
    {
        /**
         * @var $fs \Gaufrette\Filesystem
         */
        $fs = Config::get(Config::FILESYSTE);
        $fs->delete($this->getAbsolutePath());
    }

    public function getAbsolutePath()
    {
        return sprintf("%s/%s", $this->getType(), $this->getFilename());
    }

    public function getFilename()
    {
        if ($this->filename == null) {
            return sprintf("%s.tar.gz", date("Y-m-d_H-i", $this->getTimestamp()));
        }

        return sprintf("%s.tar.gz", $this->filename);
    }

    public function archive_exists()
    {

        /**
         * @var $fs \Gaufrette\Filesystem
         */
        $fs = Config::get(Config::FILESYSTE);

        return $fs->has($this->getAbsolutePath());
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
     * @return string
     */
    public function getFilesize($decimals = 2)
    {
        $bytes = $this->getFile()->getSize();
        $size = array(
            "Bytes",
            "kB",
            "MB",
            "GB",
            "TB",
            "PB"
        );
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f %s", $bytes / pow(1024, $factor), $size[$factor]);

    }

    /**
     * @return File|boolean
     */
    public function getFile()
    {
        /**
         * @var $fs \Gaufrette\Filesystem
         */
        $fs   = Config::get(Config::FILESYSTE);
        $file = $fs->get($this->getAbsolutePath());

        if ($file->exists() === false) {
            return false;
        }

        return $file;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param \DateTime $dateTime
     */
    public function setDateTime(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @param \SplFileInfo $file
     */
    public function setFile(\SplFileInfo $file)
    {
        /**
         * @var $fs \Gaufrette\Filesystem
         */
        $fs = Config::get(Config::FILESYSTE);
        $fs->write($this->getAbsolutePath(), file_get_contents($file->getRealPath()), true);
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

        /**
         * @var $gfs \Gaufrette\Filesystem
         */
        $gfs = Config::get(Config::FILESYSTE);
        $fs  = new Filesystem();
        $fs->dumpFile($tmpFile, $gfs->read($this->getAbsolutePath()));

        $cmd = sprintf("tar xfz %s -C %s; rm -rf %s",
            $tmpFile,
            $dstFolder,
            $tmpFile
        );
        CommandHelper::executeCommand($cmd);

        $fs->remove($tmpFile);
    }

    public function insertFrom($srcFolder)
    {
        $tmpFile = sprintf("/tmp/%s.tar.gz", md5(time()));

        $cmd = sprintf("cd %s; tar -czf %s *", $srcFolder, $tmpFile);
        CommandHelper::executeCommand($cmd);

        /**
         * @var $gfs \Gaufrette\Filesystem
         */
        $gfs = Config::get(Config::FILESYSTE);

        $gfs->write($this->getAbsolutePath(), file_get_contents($tmpFile), true);
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
        return $this->dateTime->getTimestamp();
    }

    /**
     * @param int $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->dateTime = \DateTime::createFromFormat('U', $timestamp);
    }

    /**
     * @return mixed
     */
    public function getCommit($short = false)
    {
        if ($short) {
            return substr($this->commit, 0, 7);
        }

        return $this->commit;
    }

    /**
     * @param mixed $commit
     */
    public function setCommit($commit)
    {
        $this->commit = $commit;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function jsonSerialize()
    {
        return [
            "timestamp" => $this->getTimestamp(),
            "version"   => $this->getVersion(),
            "commit"    => $this->getCommit(),
            "type"      => $this->getType()
        ];
    }

}