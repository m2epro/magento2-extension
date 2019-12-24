<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Listing\Product;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Block\Adminhtml\Log\Listing\View;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Listing\Product\Grid
 */
class Grid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Listing
{
    //########################################

    protected $nameBuilder;

    public function __construct(
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        Context $context
    ) {
        $this->nameBuilder = $nameBuilder;

        parent::__construct($ebayFactory, $context);
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
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId, null, false);

            if ($listing === null) {
                $this->setJsonContent([
                    'status' => false,
                    'message' => $this->__('Listing does not exist.')
                ]);
                return $this->getResult();
            }
        } elseif ($listingProductId) {
            $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId, null, false);

            if ($listingProduct === null) {
                $this->setJsonContent([
                    'status' => false,
                    'message' => $this->__('Listing Product does not exist.')
                ]);
                return $this->getResult();
            }
        }

        $sessionViewMode = $this->getHelper('Data\Session')->getValue(
            \Ess\M2ePro\Helper\View\Ebay::NICK . '_log_listing_view_mode'
        );

        if ($sessionViewMode === null) {
            $sessionViewMode = View\Switcher::VIEW_MODE_SEPARATED;
        }

        $viewMode = $this->getRequest()->getParam(
            'view_mode',
            $sessionViewMode
        );

        $gridClass = $this->nameBuilder->buildClassName([
            \Ess\M2ePro\Helper\View\Ebay::NICK,
            'Log_Listing_Product_View',
            $viewMode,
            'Grid'
        ]);

        $response = $this->createBlock($gridClass)->toHtml();
        $this->setAjaxContent($response);

        return $this->getResult();
    }

    //########################################
}
