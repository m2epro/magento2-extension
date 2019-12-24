<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Log\Listing\Product;

use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Log\Listing\Product\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Log\Listing
{
    //########################################

    protected $filterManager;

    public function __construct(
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        Context $context
    ) {
        $this->filterManager = $filterManager;

        parent::__construct($walmartFactory, $context);
    }

    public function execute()
    {
        $listingId = $this->getRequest()->getParam(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD,
            false
        );
        $listingProductId = $this->getRequest()->getParam(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_PRODUCT_ID_FIELD,
            false
        );

        if ($listingId) {
            $listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $listingId, null, false);

            if ($listing === null) {
                $this->getMessageManager()->addErrorMessage($this->__('Listing does not exist.'));
                return $this->_redirect('*/*/index');
            }

            $this->getResult()->getConfig()->getTitle()->prepend(
                $this->__('M2E Pro Listing "%s%" Log', $listing->getTitle())
            );
        } elseif ($listingProductId) {
            $listingProduct = $this->walmartFactory->getObjectLoaded(
                'Listing\Product',
                $listingProductId,
                null,
                false
            );

            if ($listingProduct === null) {
                $this->getMessageManager()->addErrorMessage($this->__('Listing product does not exist.'));
                return $this->_redirect('*/*/index');
            }

            $this->getResult()->getConfig()->getTitle()->prepend($this->__(
                'M2E Pro Listing Product "%1%" Log',
                $this->filterManager->truncate($listingProduct->getMagentoProduct()->getName(), ['length' => 28])
            ));
        } else {
            $this->getResult()->getConfig()->getTitle()->prepend($this->__('Listings Logs & Events'));
        }

        $this->setPageHelpLink('x/WgBhAQ');
        $this->addContent($this->createBlock('Walmart_Log_Listing_Product_View'));

        return $this->getResult();
    }

    //########################################
}
