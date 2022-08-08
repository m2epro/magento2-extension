<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\License;

class Change extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->config = $config;
    }

    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPostValue();

            $key = strip_tags($post['new_license_key']);
            $this->config->setGroupValue('/license/', 'key', $key);

            try {
                $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                    \Ess\M2ePro\Model\Servicing\Task\License::NAME
                );
            } catch (\Exception $e) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);

                return $this->getResult();
            }

            /** @var \Ess\M2ePro\Helper\Module\License $licenseHelper */
            $licenseHelper = $this->getHelper('Module\License');
            if (!$licenseHelper->getKey() || !$licenseHelper->getDomain() || !$licenseHelper->getIp()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $this->__('You are trying to use the unknown License Key.'),
                ]);

                return $this->getResult();
            }

            $this->setJsonContent([
                'success' => true,
                'message' => $this->__('The License Key has been updated.'),
            ]);

            return $this->getResult();
        }

        $this->setAjaxContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Settings\Tabs\License\Change::class)
        );
        return $this->getResult();
    }
}
