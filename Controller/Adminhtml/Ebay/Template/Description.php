<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Description
 */
abstract class Description extends Template
{
    protected $phpEnvironmentRequest;
    protected $productModel;

    public function __construct(
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Magento\Catalog\Model\Product $productModel,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->productModel = $productModel;

        parent::__construct($templateManager, $ebayFactory, $context);
    }

    protected function isMagentoProductExists($id)
    {
        $productCollection = $this->productModel
            ->getCollection()
            ->addIdFilter($id);

        return (bool)$productCollection->getSize();
    }
}
