<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template\GetGrid
 */
class GetGrid extends Account
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobalData,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);

        $this->helperDataGlobalData = $helperDataGlobalData;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->ebayFactory->getObjectLoaded('Account', $id);

        $this->helperDataGlobalData->setValue('edit_account', $model);

        $this->setAjaxContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\Feedback\Template\Grid::class)
        );

        return $this->getResult();
    }
}
