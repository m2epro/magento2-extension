<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\HealthStatus;

use Ess\M2ePro\Controller\Adminhtml\HealthStatus;

class Save extends HealthStatus
{
    protected $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ){
        $this->moduleConfig = $moduleConfig;
        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        $settings = $this->getRequest()->getPostValue('settings');
        $settings = $this->helperFactory->getObject('Data')->jsonDecode($settings);

        if (empty($settings)) {
            $this->setAjaxContent(json_encode(['success' => false]), false);
            return $this->getResult();
        }

        foreach ($settings as $settingEntity) {

            if (!isset($settingEntity['group'], $settingEntity['key'], $settingEntity['value'])) {

                $this->setAjaxContent(json_encode(['success' => false]), false);
                return $this->getResult();
            }

            $this->moduleConfig->setGroupValue(
                $settingEntity['group'], $settingEntity['key'], $settingEntity['value']
            );
        }

        $this->setAjaxContent(json_encode(['success' => true]), false);
        return $this->getResult();
    }

    //########################################
}