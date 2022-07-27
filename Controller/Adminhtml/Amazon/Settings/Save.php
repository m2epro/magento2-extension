<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

class Save extends Settings
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Configuration */
    protected $configuration;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Configuration $configuration,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->configuration = $configuration;
    }

    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->setJsonContent(['success' => false]);
            return $this->getResult();
        }

        $this->configuration->setConfigValues($this->getRequest()->getParams());
        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }

    //########################################
}
