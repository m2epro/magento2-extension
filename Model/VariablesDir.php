<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

/**
 * Class \Ess\M2ePro\Model\VariablesDir
 */
class VariablesDir extends AbstractModel
{
    const BASE_NAME = 'M2ePro';

    private $_fileDriver = null;
    private $_childFolder = null;
    private $_pathVariablesDirBase = null;
    private $_pathVariablesDirChildFolder = null;

    //########################################

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Magento\Framework\Filesystem $filesystem,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->_fileDriver = $driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);

        !isset($data['child_folder']) && $data['child_folder'] = null;
        $data['child_folder'] === '' && $data['child_folder'] = null;

        $varDir = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $this->_pathVariablesDirBase = $varDir->getAbsolutePath() . self::BASE_NAME;

        if ($data['child_folder'] !== null) {
            if ($data['child_folder']{0} != DIRECTORY_SEPARATOR) {
                $data['child_folder'] = DIRECTORY_SEPARATOR.$data['child_folder'];
            }
            if ($data['child_folder']{strlen($data['child_folder'])-1} != DIRECTORY_SEPARATOR) {
                $data['child_folder'] .= DIRECTORY_SEPARATOR;
            }

            $this->_pathVariablesDirChildFolder = $this->_pathVariablesDirBase.$data['child_folder'];
            $this->_pathVariablesDirBase .= DIRECTORY_SEPARATOR;
            $this->_childFolder = $data['child_folder'];
        } else {
            $this->_pathVariablesDirBase .= DIRECTORY_SEPARATOR;
            $this->_pathVariablesDirChildFolder = $this->_pathVariablesDirBase;
            $this->_childFolder = '';
        }

        $this->_pathVariablesDirBase = str_replace(
            ['/','\\'],
            DIRECTORY_SEPARATOR,
            $this->_pathVariablesDirBase
        );
        $this->_pathVariablesDirChildFolder = str_replace(
            ['/','\\'],
            DIRECTORY_SEPARATOR,
            $this->_pathVariablesDirChildFolder
        );
        $this->_childFolder = str_replace(['/','\\'], DIRECTORY_SEPARATOR, $this->_childFolder);

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function getBasePath()
    {
        return $this->_pathVariablesDirBase;
    }

    public function getPath()
    {
        return $this->_pathVariablesDirChildFolder;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isBaseExist()
    {
        return $this->_fileDriver->isDirectory($this->getBasePath());
    }

    /**
     * @return bool
     */
    public function isExist()
    {
        return $this->_fileDriver->isDirectory($this->getPath());
    }

    // ---------------------------------------

    public function createBase()
    {
        if ($this->isBaseExist()) {
            return;
        }

        $this->_fileDriver->createDirectory($this->getBasePath(), 0777);
    }

    public function create()
    {
        if ($this->isExist()) {
            return;
        }

        $this->createBase();

        if ($this->_childFolder != '') {
            $tempPath = $this->getBasePath();
            $tempChildFolders = explode(
                DIRECTORY_SEPARATOR,
                substr($this->_childFolder, 1, strlen($this->_childFolder)-2)
            );

            foreach ($tempChildFolders as $key => $value) {
                if (!$this->_fileDriver->isDirectory($tempPath.$value.DIRECTORY_SEPARATOR)) {
                    $this->_fileDriver->createDirectory($tempPath.$value.DIRECTORY_SEPARATOR, 0777);
                }
                $tempPath = $tempPath.$value.DIRECTORY_SEPARATOR;
            }
        } else {
            $this->_fileDriver->createDirectory($this->getPath(), 0777);
        }
    }

    // ---------------------------------------

    public function removeBase()
    {
        if (!$this->isBaseExist()) {
            return;
        }

        $this->_fileDriver->deleteDirectory($this->getBasePath());
    }

    public function removeBaseForce()
    {
        if (!$this->isBaseExist()) {
            return;
        }

        $directoryIterator = new \RecursiveDirectoryIterator($this->getBasePath(), \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $path) {
            $path->isFile()
                ? $this->_fileDriver->deleteFile($path->getPathname())
                : $this->_fileDriver->deleteDirectory($path->getPathname());
        }

        $this->_fileDriver->deleteDirectory($this->getBasePath());
    }

    public function remove()
    {
        if (!$this->isExist()) {
            return;
        }

        $this->_fileDriver->deleteDirectory($this->getPath());
    }

    //########################################
}
