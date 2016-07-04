<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class VariablesDir
{
    const BASE_NAME = 'M2ePro';

    private $_childFolder = NULL;
    private $_pathVariablesDirBase = NULL;
    private $_pathVariablesDirChildFolder = NULL;

    //########################################

    public function __construct(\Magento\Framework\Filesystem $filesystem)
    {
        $args = func_get_args();
        empty($args[0]) && $args[0] = array();
        $params = $args[0];

        !isset($params['child_folder']) && $params['child_folder'] = NULL;
        $params['child_folder'] === '' && $params['child_folder'] = NULL;

        $varDir = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $this->_pathVariablesDirBase = $varDir->getAbsolutePath();

        if (!is_null($params['child_folder'])) {

            if ($params['child_folder']{0} != DIRECTORY_SEPARATOR) {
                $params['child_folder'] = DIRECTORY_SEPARATOR.$params['child_folder'];
            }
            if ($params['child_folder']{strlen($params['child_folder'])-1} != DIRECTORY_SEPARATOR) {
                $params['child_folder'] .= DIRECTORY_SEPARATOR;
            }

            $this->_pathVariablesDirChildFolder = $this->_pathVariablesDirBase.$params['child_folder'];
            $this->_pathVariablesDirBase .= DIRECTORY_SEPARATOR;
            $this->_childFolder = $params['child_folder'];

        } else {

            $this->_pathVariablesDirBase .= DIRECTORY_SEPARATOR;
            $this->_pathVariablesDirChildFolder = $this->_pathVariablesDirBase;
            $this->_childFolder = '';
        }

        $this->_pathVariablesDirBase = str_replace(array('/','\\'),
            DIRECTORY_SEPARATOR,$this->_pathVariablesDirBase);
        $this->_pathVariablesDirChildFolder = str_replace(array('/','\\'),
            DIRECTORY_SEPARATOR,$this->_pathVariablesDirChildFolder);
        $this->_childFolder = str_replace(array('/','\\'),DIRECTORY_SEPARATOR,$this->_childFolder);
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
        return @is_dir($this->getBasePath());
    }

    /**
     * @return bool
     */
    public function isExist()
    {
        return @is_dir($this->getPath());
    }

    // ---------------------------------------

    public function createBase()
    {
        if ($this->isBaseExist()) {
            return;
        }

        if (!@mkdir($this->getBasePath(), 0777, true)) {
            throw new \Ess\M2ePro\Model\Exception('M2ePro base var dir creation is failed.');
        }
    }

    public function create()
    {
        if ($this->isExist()) {
            return;
        }

        $this->createBase();

        if ($this->_childFolder != '') {

            $tempPath = $this->getBasePath();
            $tempChildFolders = explode(DIRECTORY_SEPARATOR,
                substr($this->_childFolder,1,strlen($this->_childFolder)-2));

            foreach ($tempChildFolders as $key=>$value) {
                if (!is_dir($tempPath.$value.DIRECTORY_SEPARATOR)) {
                    if (!@mkdir($tempPath.$value.DIRECTORY_SEPARATOR, 0777, true)) {
                        throw new \Ess\M2ePro\Model\Exception('Custom var dir creation is failed.');
                    }
                }
                $tempPath = $tempPath.$value.DIRECTORY_SEPARATOR;
            }
        } else {
            if (!@mkdir($this->getPath(), 0777, true)) {
                throw new \Ess\M2ePro\Model\Exception('Custom var dir creation is failed.');
            }
        }
    }

    // ---------------------------------------

    public function removeBase()
    {
        if (!$this->isBaseExist()) {
            return;
        }

        if (!@rmdir($this->getBasePath())) {
            throw new \Ess\M2ePro\Model\Exception('M2ePro base var dir removing is failed.');
        }
    }

    public function removeBaseForce()
    {
        if (!$this->isBaseExist()) {
            return;
        }

        $directoryIterator = new \RecursiveDirectoryIterator($this->getBasePath(), \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $path) {
            $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
        }

        if (!@rmdir($this->getBasePath())) {
            throw new \Ess\M2ePro\Model\Exception('M2ePro base var dir removing is failed.');
        }
    }

    public function remove()
    {
        if (!$this->isExist()) {
            return;
        }

        if (!@rmdir($this->getPath())) {
            throw new \Ess\M2ePro\Model\Exception('Custom var dir removing is failed.');
        }
    }

    //########################################
}