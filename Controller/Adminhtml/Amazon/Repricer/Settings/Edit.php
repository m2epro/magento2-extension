<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Repricer\Settings;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Repricer;

class Edit extends Repricer
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobalData;

    /**
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobalData
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperData = $helperData;
        $this->helperDataGlobalData = $helperDataGlobalData;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        $account = null;
        try {
            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->amazonFactory->getObjectLoaded('Account', $id);
        } catch (\Exception $e) {
        }

        if ($id && !$account->getId()) {
            $this->messageManager->addError($this->__('Account does not exist.'));

            return $this->_redirect('*/amazon_account');
        }

        $this->helperDataGlobalData->setValue('edit_account', $account);

        $headerText = $this->__('Additional Settings') . ' "' . $this->helperData->escapeHtml(
            $account->getTitle()
        ) . '"';

        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Repricer\Edit::class));

        return $this->getResultPage();
    }
}
