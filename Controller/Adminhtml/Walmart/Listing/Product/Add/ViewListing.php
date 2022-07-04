<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

class ViewListing extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->sessionHelper = $sessionHelper;
    }

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');

        if (empty($listingId)) {
            return $this->_redirect('*/walmart_listing/index');
        }

        $this->sessionHelper->setValue('temp_products', []);

        return $this->_redirect('*/walmart_listing/view', [
            'id' => $listingId
        ]);
    }
}
