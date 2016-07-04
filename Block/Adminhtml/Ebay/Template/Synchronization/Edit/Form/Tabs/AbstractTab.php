<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

abstract class AbstractTab extends AbstractForm
{
    public function isCustom()
    {
        $isCustom = $this->getHelper('Data\GlobalData')->getValue('is_custom');
        if (!is_null($isCustom)) {
            return (bool)$isCustom;
        }

        return false;
    }

    public function getTitle()
    {
        if ($this->isCustom()) {
            $customTitle = $this->getHelper('Data\GlobalData')->getValue('custom_title');
            return !is_null($customTitle) ? $customTitle : '';
        }

        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_synchronization');

        if (is_null($template)) {
            return '';
        }

        return $template->getTitle();
    }

    //########################################

    public function getFormData()
    {
        $template = $this->getHelper('Data\GlobalData')->getValue('ebay_template_synchronization');

        if (is_null($template) || is_null($template->getId())) {
            return array();
        }

        $data = array_merge($template->getData(), $template->getChildObject()->getData());

        return $data;
    }
}