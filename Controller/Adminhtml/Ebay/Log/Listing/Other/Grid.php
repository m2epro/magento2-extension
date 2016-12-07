<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Listing\Other;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Block\Adminhtml\Log\Listing\View;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Listing
{
    //########################################

    protected $nameBuilder;

    public function __construct(
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        Context $context)
    {
        $this->nameBuilder = $nameBuilder;

        parent::__construct($ebayFactory, $context);
    }

    public function execute()
    {
        $listingId = $this->getRequest()->getParam(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\AbstractGrid::LISTING_ID_FIELD, false
        );

        if ($listingId) {
            $listingOther = $this->ebayFactory->getObjectLoaded('Listing\Other', $listingId, null, false);

            if (is_null($listingOther)) {
                $this->setJsonContent([
                    'status' => false,
                    'message' => $this->__('3rd Party Listing does not exist.')
                ]);
                return $this->getResult();
            }
        }

        $sessionViewMode = $this->getHelper('Data\Session')->getValue(
            \Ess\M2ePro\Helper\View\Ebay::NICK . '_log_listing_view_mode'
        );

        if (is_null($sessionViewMode)) {
            $sessionViewMode = View\Switcher::VIEW_MODE_SEPARATED;
        }

        $viewMode = $this->getRequest()->getParam(
            'view_mode', $sessionViewMode
        );

        $gridClass = $this->nameBuilder->buildClassName([
            \Ess\M2ePro\Helper\View\Ebay::NICK,
            'Log\Listing\Other\View',
            $viewMode,
            'Grid'
        ]);

        $response = $this->createBlock($gridClass)->toHtml();
        $this->setAjaxContent($response);

        return $this->getResult();
    }

    //########################################
}