<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\ProductType;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Walmart\AbstractProductType
{
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $this->addContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\ProductType::class)
        );

        $this->getResultPage()
             ->getConfig()
             ->getTitle()
             ->prepend((string)__('Product Types'));

        $this->setPageHelpLink('walmart-product-type');

        return $this->getResultPage();
    }
}
