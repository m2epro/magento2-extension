<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

class Save extends Settings
{
    private \Ess\M2ePro\Model\Config\ListingSynchronization $listingSynchronizationConfig;

    public function __construct(
        \Ess\M2ePro\Model\Config\ListingSynchronization $listingSynchronizationConfig,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->listingSynchronizationConfig = $listingSynchronizationConfig;
    }

    public function execute()
    {
        $this->listingSynchronizationConfig->setAmazonMode((int)$this->getRequest()->getParam('instructions_mode'));
        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }
}
