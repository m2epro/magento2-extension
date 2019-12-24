<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Listing\Other;

use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Listing\Other\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Listing
{
    //########################################

    protected $filterManager;

    public function __construct(
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        Context $context
    ) {
        $this->filterManager = $filterManager;

        parent::__construct($ebayFactory, $context);
    }

    public function execute()
    {
        $listingId = $this->getRequest()->getParam(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\AbstractGrid::LISTING_ID_FIELD,
            false
        );
        $isListings = $this->getRequest()->getParam('listings', false);

        if ($isListings) {
            $this->getResult()->getConfig()->getTitle()->prepend($this->__('3rd Party Listings Log'));
        } elseif ($listingId) {
            $listingOther = $this->ebayFactory->getObjectLoaded('Listing\Other', $listingId, null, false);

            if ($listingOther === null) {
                $this->getMessageManager()->addErrorMessage($this->__('3rd Party Listing does not exist.'));
                return $this->_redirect('*/*/index');
            }

            $this->getResult()->getConfig()->getTitle()->prepend(
                $this->__(
                    '3rd Party Listing "%s%" Log',
                    $this->filterManager->truncate($listingOther->getChildObject()->getTitle(), ['length' => 28])
                )
            );
        } else {
            $this->getResult()->getConfig()->getTitle()->prepend($this->__('Listings Logs & Events'));
        }

        $this->addContent($this->createBlock('Ebay_Log_Listing_Other_View'));

        return $this->getResult();
    }
    //########################################
}
