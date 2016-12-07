<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Listing\Other;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\View;
use Ess\M2ePro\Controller\Adminhtml\Context;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Listing
{
    //########################################

    protected $nameBuilder;

    public function __construct(
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        Context $context
    )
    {
        $this->nameBuilder = $nameBuilder;

        parent::__construct($amazonFactory, $context);
    }

    public function execute()
    {
        $listingId = $this->getRequest()->getParam(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\AbstractGrid::LISTING_ID_FIELD, false
        );

        if ($listingId) {
            $listingOther = $this->amazonFactory->getObjectLoaded('Listing\Other', $listingId, null, false);

            if (is_null($listingOther)) {
                $this->setJsonContent([
                    'status' => false,
                    'message' => $this->__('3rd Party Listing does not exist.')
                ]);
                return $this->getResult();
            }
        }

        $sessionViewMode = $this->getHelper('Data\Session')->getValue(
            \Ess\M2ePro\Helper\View\Amazon::NICK . '_log_listing_view_mode'
        );

        if (is_null($sessionViewMode)) {
            $sessionViewMode = View\Switcher::VIEW_MODE_SEPARATED;
        }

        $viewMode = $this->getRequest()->getParam(
            'view_mode', $sessionViewMode
        );

        $gridClass = $this->nameBuilder->buildClassName([
            \Ess\M2ePro\Helper\View\Amazon::NICK,
            'Log\Listing\Other\View',
            $viewMode,
            'Grid'
        ]);

        $this->setAjaxContent($this->createBlock($gridClass));

        return $this->getResult();
    }

    //########################################
}