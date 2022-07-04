<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Settings;

class Save extends Settings
{
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->config = $config;
    }

    public function execute()
    {
        $this->config->setGroupValue(
            '/cron/task/walmart/listing/product/process_instructions/',
            'mode',
            (int)$this->getRequest()->getParam('instructions_mode')
        );

        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }
}
