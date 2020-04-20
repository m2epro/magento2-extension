<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs\AbstractTab
 */
abstract class AbstractTab extends AbstractForm
{
    public function isCustom()
    {
        $isCustom = $this->getHelper('Data\GlobalData')->getValue('is_custom');
        if ($isCustom !== null) {
            return (bool)$isCustom;
        }

        return false;
    }

    public function getTitle()
    {
        if ($this->isCustom()) {
            $customTitle = $this->getHelper('Data\GlobalData')->getValue('custom_title');
            return $customTitle !== null ? $customTitle : '';
        }

        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_synchronization');

        if ($template === null) {
            return '';
        }

        return $template->getTitle();
    }

    //########################################

    public function getFormData()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_synchronization');

        if ($template === null || $template->getId() === null) {
            return [];
        }

        $data = array_merge($template->getData(), $template->getChildObject()->getData());

        return $data;
    }
}
