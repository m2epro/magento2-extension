<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

class Save extends Settings
{
    private \Ess\M2ePro\Model\Config\ListingSynchronization $listingSynchronizationConfig;

    public function __construct(
        \Ess\M2ePro\Model\Config\ListingSynchronization $listingSynchronizationConfig,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->listingSynchronizationConfig = $listingSynchronizationConfig;
    }

    public function execute()
    {
        $this->listingSynchronizationConfig->setEbayMode((int)$this->getRequest()->getParam('instructions_mode'));
        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }
}
