<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Log;

use Ess\M2ePro\Controller\Adminhtml\Context;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Log
{
    //########################################

    protected $filterManager;

    public function __construct(
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        Context $context
    )
    {
        $this->filterManager = $filterManager;
        parent::__construct($ebayFactory, $context);
    }

    public function execute()
    {
        $listingProductId = $this->getRequest()->getParam('listing_product_id', false);
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId, NULL, false);

        if (!$listingProduct->getId()) {
            $this->getMessageManager()->addError($this->__('Listing Product does not exist.'));
            return $this->_redirect('*/*/index');
        }

        $logBlock = $this->createBlock('Ebay\Listing\Log');

        $this->addContent($logBlock);

        if ($listingProductId) {
            $this->getResult()->getConfig()->getTitle()->prepend($this->__(
                'M2E Pro Listing Product Log "%1%"',
                $this->filterManager->truncate($listingProduct->getMagentoProduct()->getName(), ['length' => 28])
            ));
        } else {
            $this->getResult()->getConfig()->getTitle()->prepend($this->__('Listing Product Logs & Actions'));
        }

        return $this->getResult();
    }

    //########################################
}