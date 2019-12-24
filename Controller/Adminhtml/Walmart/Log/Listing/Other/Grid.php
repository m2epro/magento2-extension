<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Log\Listing\Other;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\View;
use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Log\Listing\Other\Grid
 */
class Grid extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Log\Listing
{
    //########################################

    protected $nameBuilder;

    public function __construct(
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        Context $context
    ) {
        $this->nameBuilder = $nameBuilder;

        parent::__construct($walmartFactory, $context);
    }

    public function execute()
    {
        $listingId = $this->getRequest()->getParam(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\AbstractGrid::LISTING_ID_FIELD,
            false
        );

        if ($listingId) {
            $listingOther = $this->walmartFactory->getObjectLoaded('Listing\Other', $listingId, null, false);

            if ($listingOther === null) {
                $this->setJsonContent([
                    'status' => false,
                    'message' => $this->__('3rd Party Listing does not exist.')
                ]);
                return $this->getResult();
            }
        }

        $sessionViewMode = $this->getHelper('Data\Session')->getValue(
            \Ess\M2ePro\Helper\View\Walmart::NICK . '_log_listing_view_mode'
        );

        if ($sessionViewMode === null) {
            $sessionViewMode = View\Switcher::VIEW_MODE_SEPARATED;
        }

        $viewMode = $this->getRequest()->getParam(
            'view_mode',
            $sessionViewMode
        );

        $gridClass = $this->nameBuilder->buildClassName([
            \Ess\M2ePro\Helper\View\Walmart::NICK,
            'Log_Listing_Other_View',
            $viewMode,
            'Grid'
        ]);

        $this->setAjaxContent($this->createBlock($gridClass));

        return $this->getResult();
    }

    //########################################
}
