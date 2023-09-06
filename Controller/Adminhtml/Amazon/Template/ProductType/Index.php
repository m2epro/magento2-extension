<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $content = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType::class);

        $this->getResultPage()->getConfig()->getTitle()->prepend('Product Types');
        $this->addContent($content);
        $this->setPageHelpLink('amazon-product-type');

        return $this->getResultPage();
    }
}
