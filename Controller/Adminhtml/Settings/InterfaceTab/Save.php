<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\InterfaceTab;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Settings\InterfaceTab\Save
 */
class Save extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Configuration  $moduleHelperConfiguration,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->moduleConfiguration = $moduleHelperConfiguration;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->setJsonContent(['success' => false]);

            return $this->getResult();
        }

        $this->moduleConfiguration->setConfigValues($this->getRequest()->getParams());
        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }

    //########################################
}
