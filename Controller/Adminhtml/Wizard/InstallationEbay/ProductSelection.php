<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class ProductSelection extends InstallationEbay
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $ebayViewHelper, $nameBuilder, $context);

        $this->sessionHelper = $sessionHelper;
    }

    public function execute()
    {
        $listingId = $this->ebayFactory->getObject('Listing')->getCollection()->getLastItem()->getId();

        $productAddSessionData = $this->sessionHelper->getValue('ebay_listing_product_add');
        $source = isset($productAddSessionData['source']) ? $productAddSessionData['source'] : null;

        $this->sessionHelper->setValue('ebay_listing_product_add', $productAddSessionData);
        return $this->_redirect(
            '*/ebay_listing_product_add/index',
            [
                'clear' => true,
                'step'  => 1,
                'wizard' => true,
                'id' => $listingId,
                'listing_creation' => true,
                'source' => $source
            ]
        );
    }
}
