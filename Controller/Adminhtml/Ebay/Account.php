<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay;

abstract class Account extends Main
{
    /** @var \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update */
    protected $storeCategoryUpdate;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Store */
    private $componentEbayCategoryStore;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->storeCategoryUpdate = $storeCategoryUpdate;
        $this->componentEbayCategoryStore = $componentEbayCategoryStore;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_configuration_accounts');
    }
}
