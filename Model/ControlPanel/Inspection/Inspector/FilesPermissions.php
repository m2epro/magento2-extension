<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Helper\Module;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File;
use Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;

class FilesPermissions implements InspectorInterface
{
    /** @var array */
    private $_unWritable = [];

    /** @var array */
    private $_checked = [];

    /** @var ComponentRegistrarInterface */
    private $componentRegistrar;

    /** @var \Magento\Framework\Filesystem\Driver\File */
    private $fileDriver;

    /** @var IssueFactory  */
    private $issueFactory;

    public function __construct(
        ComponentRegistrarInterface $componentRegistrar,
        File $fileDriver,
        IssueFactory $issueFactory
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->fileDriver         = $fileDriver;
        $this->issueFactory = $issueFactory;
    }

    //########################################

    public function process()
    {
        $this->processModuleFiles();

        $issues = [];

        if (!empty($this->_unWritable)) {
            $issues[] = $this->issueFactory->create(
                'Has unwriteable files \ directories',
                array_keys($this->_unWritable)
            );
        }

        return $issues;
    }

    private function processModuleFiles()
    {

        $fullPath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, Module::IDENTIFIER)
            .DIRECTORY_SEPARATOR;

        $directoryIterator = new \RecursiveDirectoryIterator($fullPath, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $fileObj) {
            /**@var \SplFileObject $fileObj */
            $this->check($fileObj);
        }
    }

    private function check(\SplFileInfo $object)
    {
        if (isset($this->_unWritable[$object->getRealPath()])) {
            return;
        }

        if ($this->fileDriver->isExists($object->getRealPath())
            && !$this->fileDriver->isWritable($object->getRealPath())) {
            $this->_unWritable[$object->getRealPath()] = true;
        }

        $this->_checked[$object->getRealPath()] = true;
    }

    //########################################
}
