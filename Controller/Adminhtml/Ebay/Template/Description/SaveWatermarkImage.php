<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description\SaveWatermarkImage
 */
class SaveWatermarkImage extends Description
{
    protected $driverPool;

    //########################################

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Magento\Catalog\Model\Product $productModel,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->driverPool = $driverPool;
        parent::__construct($phpEnvironmentRequest, $productModel, $templateManager, $ebayFactory, $context);
    }

    //########################################

    public function execute()
    {
        $templateData = $this->getRequest()->getPost('description');

        $watermarkImageFile = $this->phpEnvironmentRequest->getFiles('watermark_image');

        if ($templateData['id'] === null || empty($watermarkImageFile['tmp_name'])) {
            $this->setJsonContent([
                'result' => false
            ]);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\VariablesDir $varDir */
        $varDir = $this->modelFactory->getObject('VariablesDir', ['data' => [
            'child_folder' => 'ebay/template/description/watermarks'
        ]]);

        $watermarkPath = $varDir->getPath().(int)$templateData['id'].'.png';

        $fileDriver = $this->driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
        if ($fileDriver->isFile($watermarkPath)) {
            $fileDriver->deleteFile($watermarkPath);
        }

        /** @var \Ess\M2ePro\Model\Template\Description $template */
        $template = $this->ebayFactory->getObjectLoaded('Template\Description', $templateData['id'], null, false);

        if ($template->getId() === null) {
            $this->setJsonContent([
                'result' => false
            ]);
            return $this->getResult();
        }

        $template->getChildObject()->updateWatermarkHashes();

        $data = [
            'watermark_image' => file_get_contents($watermarkImageFile['tmp_name'])
        ];

        $template->getChildObject()->addData($data);
        $template->save();

        $this->setJsonContent([
            'result' => true
        ]);
        return $this->getResult();
    }

    //########################################
}
