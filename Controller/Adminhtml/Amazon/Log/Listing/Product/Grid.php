<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Listing\Product;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Block\Adminhtml\Log\Listing\View;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Listing
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $helperDataSession;

    protected $nameBuilder;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $helperDataSession,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        Context $context
    ) {
        $this->helperDataSession = $helperDataSession;
        $this->nameBuilder = $nameBuilder;

        parent::__construct($amazonFactory, $context);
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
            $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $listingId, null, false);

            if ($listing === null) {
                $this->setJsonContent([
                    'status' => false,
                    'message' => $this->__('Listing does not exist.')
                ]);
                return $this->getResult();
            }
        } elseif ($listingProductId) {
            $listingProduct = $this->amazonFactory->getObjectLoaded(
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

        $sessionViewMode = $this->helperDataSession->getValue(
            \Ess\M2ePro\Helper\View\Amazon::NICK . '_log_listing_view_mode'
        );

        if ($sessionViewMode === null) {
            $sessionViewMode = View\Switcher::VIEW_MODE_SEPARATED;
        }

        $viewMode = $this->getRequest()->getParam(
            'view_mode',
            $sessionViewMode
        );

        if ($viewMode === View\Switcher::VIEW_MODE_GROUPED) {
            $gridClass = \Ess\M2ePro\Block\Adminhtml\Amazon\Log\Listing\Product\View\Grouped\Grid::class;
        }
        else {
            $gridClass = \Ess\M2ePro\Block\Adminhtml\Amazon\Log\Listing\Product\View\Separated\Grid::class;
        }

        $block = $this->getLayout()->createBlock($gridClass);
        $this->setAjaxContent($block->toHtml());

        return $this->getResult();
    }
}
