<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Log\Listing\Product;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\View;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Log\Listing
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    /** @var \Magento\Framework\Code\NameBuilder */
    private $nameBuilder;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->sessionHelper = $sessionHelper;
        $this->nameBuilder = $nameBuilder;
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
                $this->setJsonContent([
                    'status' => false,
                    'message' => $this->__('Listing does not exist.')
                ]);
                return $this->getResult();
            }
        } elseif ($listingProductId) {
            $listingProduct = $this->walmartFactory->getObjectLoaded(
                'Listing\Product',
                $listingProductId,
                null,
                false
            );

            if ($listingProduct === null) {
                $this->setJsonContent([
                    'status' => false,
                    'message' => $this->__('Listing product does not exist.')
                ]);
                return $this->getResult();
            }
        }

        $sessionViewMode = $this->sessionHelper->getValue(
            \Ess\M2ePro\Helper\View\Walmart::NICK . '_log_listing_view_mode'
        );

        if ($sessionViewMode === null) {
            $sessionViewMode = View\Switcher::VIEW_MODE_SEPARATED;
        }

        $viewMode = $this->getRequest()->getParam(
            'view_mode',
            $sessionViewMode
        );

        if ($viewMode === View\Switcher::VIEW_MODE_GROUPED) {
            $gridClass = \Ess\M2ePro\Block\Adminhtml\Walmart\Log\Listing\Product\View\Grouped\Grid::class;
        }
        else {
            $gridClass = \Ess\M2ePro\Block\Adminhtml\Walmart\Log\Listing\Product\View\Separated\Grid::class;
        }

        $block = $this->getLayout()->createBlock($gridClass);
        $this->setAjaxContent($block->toHtml());

        return $this->getResult();
    }
}
