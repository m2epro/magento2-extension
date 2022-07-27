<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class ProductSelection extends InstallationAmazon
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $amazonViewHelper, $nameBuilder, $context);

        $this->sessionHelper = $sessionHelper;
    }

    public function execute()
    {
        $listingId = $this->amazonFactory->getObject('Listing')->getCollection()->getLastItem()->getId();

        $source = $this->sessionHelper->getValue('products_source');

        return $this->_redirect(
            '*/amazon_listing_product_add/index',
            [
                'step' => 2,
                'source' => $source,
                'id' => $listingId,
                'new_listing' => true,
                'wizard' => true,
            ]
        );
    }
}
