<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Helper\Module;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory as ParentFactory;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;
use Ess\M2ePro\Model\ControlPanel\Inspection\Result\Factory;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Filesystem\Driver\File;

class FilesPermissions extends AbstractInspection implements InspectorInterface
{
    /** @var array */
    protected $_unWritable = [];

    /** @var array */
    protected $_checked = [];

    /** @var ComponentRegistrarInterface */
    protected $componentRegistrar;

    protected $fileDriver;

    public function __construct(
        Factory $resultFactory,
        HelperFactory $helperFactory,
        ModelFactory $modelFactory,
        UrlInterface $urlBuilder,
        ResourceConnection $resourceConnection,
        FormKey $formKey,
        ParentFactory $parentFactory,
        ActiveRecordFactory $activeRecordFactory,
        ComponentRegistrarInterface $componentRegistrar,
        File $fileDriver,
        array $_params = []
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->fileDriver         = $fileDriver;

        parent::__construct(
            $resultFactory,
            $helperFactory,
            $modelFactory,
            $urlBuilder,
            $resourceConnection,
            $formKey,
            $parentFactory,
            $activeRecordFactory,
            $_params
        );
    }

    //########################################

    public function getTitle()
    {
        return 'Files and Folders permissions';
    }

    public function getExecutionSpeed()
    {
        return Manager::EXECUTION_SPEED_SLOW;
    }

    public function getGroup()
    {
        return Manager::GROUP_STRUCTURE;
    }

    //########################################

    public function process()
    {
        $this->processModuleFiles();

        $issues = [];

        if (!empty($this->_unWritable)) {
            $issues[] = $this->resultFactory->createError(
                $this,
                'Has unwriteable files \ directories',
                array_keys($this->_unWritable)
            );
        }

        return $issues;
    }

    protected function processModuleFiles()
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

    protected function check(\SplFileInfo $object)
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
