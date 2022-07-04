<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

class Save extends Settings
{
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->config = $config;
    }

    public function execute()
    {
        $this->config->setGroupValue(
            '/cron/task/amazon/listing/product/process_instructions/',
            'mode',
            (int)$this->getRequest()->getParam('instructions_mode')
        );

        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }
}
