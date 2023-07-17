<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class NewAction extends Account
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $helperAmazon;

    /**
     * @param \Ess\M2ePro\Helper\Component\Amazon $helperAmazon
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon $helperAmazon,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->helperAmazon = $helperAmazon;
    }

    public function execute()
    {
        $marketplaces = $this->helperAmazon->getMarketplacesAvailableForApiCreation();
        if ($marketplaces->getSize() <= 0) {
            $message = 'You should select and update at least one Amazon marketplace.';
            $this->messageManager->addErrorMessage($this->__($message));
            return $this->_redirect('*/amazon_account');
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Add Account')
        );

        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Create::class)
        );
        $this->setPageHelpLink('accounts');

        return $this->getResultPage();
    }
}
