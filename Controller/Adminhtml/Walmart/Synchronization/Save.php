<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Settings;

class Save extends Settings
{
    private \Ess\M2ePro\Model\Config\ListingSynchronization $listingSynchronizationConfig;

    public function __construct(
        \Ess\M2ePro\Model\Config\ListingSynchronization $listingSynchronizationConfig,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->listingSynchronizationConfig = $listingSynchronizationConfig;
    }

    public function execute()
    {
        $this->listingSynchronizationConfig->setWalmartMode((int)$this->getRequest()->getParam('instructions_mode'));
        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }
}
