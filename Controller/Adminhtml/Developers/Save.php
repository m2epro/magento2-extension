<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Developers;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Developers\Save
 */
class Save extends \Ess\M2ePro\Controller\Adminhtml\Developers
{
    protected $synchronizationConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Synchronization $synchronizationConfig,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->synchronizationConfig = $synchronizationConfig;
        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->setJsonContent(['success' => false]);
            return $this->getResult();
        }

        $this->synchronizationConfig->setGroupValue(
            '/global/magento_products/inspector/',
            'mode',
            (int)$post['inspector_mode']
        );

        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }

    //########################################
}
