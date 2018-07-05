<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

use Ess\M2ePro\Model\Exception;
use Magento\Framework\Component\ComponentRegistrar;
use Ess\M2ePro\Helper\Module;

class ChangedSources extends \Ess\M2ePro\Model\Servicing\Task
{
    protected $componentRegistrar;
    protected $filesystemDriver;
    protected $fileReaderFactory;

    //########################################

    public function __construct(
        \Magento\Eav\Model\Config $config,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        ComponentRegistrar $componentRegistrar,
        \Magento\Framework\Filesystem\Driver\File $filesystemDriver,
        \Magento\Framework\Filesystem\File\ReadFactory $fileReaderFactory
    ){
        parent::__construct(
            $config,
            $cacheConfig,
            $storeManager,
            $modelFactory,
            $helperFactory,
            $resource,
            $activeRecordFactory,
            $parentFactory
        );

        $this->componentRegistrar = $componentRegistrar;
        $this->filesystemDriver   = $filesystemDriver;
        $this->fileReaderFactory  = $fileReaderFactory;
    }

    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'changed_sources';
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $responseData = array();

        try {

            $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('files','get','info');
            $dispatcherObject->process($connectorObj);

            $responseData = $connectorObj->getResponseData();

        } catch (Exception $e) {
            $this->helperFactory->getObject('Module\Exception')->process($e);
        }

        if (count($responseData) <= 0) {
            return array();
        }

        $requestData = array();

        foreach ($responseData['files_info'] as $info) {

            if (!in_array($info['path'], $this->getImportantFiles())) {
                continue;
            }

            $basePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, Module::IDENTIFIER);
            $fullPath = $basePath .DIRECTORY_SEPARATOR. $info['path'];

            if (!$this->filesystemDriver->isExists($fullPath)) {

                $requestData[] = array(
                    'path'    => $info['path'],
                    'hash'    => NULL,
                    'content' => NULL,
                );
                continue;
            }

            /** @var \Magento\Framework\Filesystem\File\Read $fileReader */
            $fileReader = $this->fileReaderFactory->create($fullPath, $this->filesystemDriver);
            $fileContent = $fileReader->readAll();
            $fileContent = str_replace(array("\r\n","\n\r",PHP_EOL), chr(10), $fileContent);
            $contentHash = md5($fileContent);

            if ($contentHash != $info['hash']) {

                $requestData[] = array(
                    'path'    => $info['path'],
                    'hash'    => $contentHash,
                    'content' => $fileContent,
                );
            }
        }

        return $requestData;
    }

    //########################################

    public function processResponseData(array $data) {}

    //########################################

    //todo Ruslan is going to change this list
    private function getImportantFiles()
    {
        return array(
            'Model/Ebay/Actions/Processor.php',
            'Model/Amazon/Actions/Processor.php'
        );
    }

    //########################################
}