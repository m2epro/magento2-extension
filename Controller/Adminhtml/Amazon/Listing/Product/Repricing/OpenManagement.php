<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Repricing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Repricing\OpenManagement
 */
class OpenManagement extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Repricing */
    private $helperAmazonRepricing;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Repricing $helperAmazonRepricing,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperAmazonRepricing = $helperAmazonRepricing;
    }

    public function execute()
    {
        $listingId     = $this->getRequest()->getParam('id');
        $accountId = $this->getRequest()->getParam('id');

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $accountId, null, false);

        if (!$account->getId()) {
            $this->getMessageManager()->addError($this->__('Account does not exist.'));
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        return $this->_redirect($this->helperAmazonRepricing->getManagementUrl($account));
    }
}
