<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $this->getResultPage()
             ->getConfig()
             ->getTitle()->prepend('Product Types');

        $this->setPageHelpLink('amazon-product-type');

        return $this->getResultPage();
    }
}
