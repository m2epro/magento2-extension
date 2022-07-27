<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

abstract class AbstractTab extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    protected $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function isCustom()
    {
        $isCustom = $this->globalDataHelper->getValue('is_custom');
        if ($isCustom !== null) {
            return (bool)$isCustom;
        }

        return false;
    }

    public function getTitle()
    {
        if ($this->isCustom()) {
            $customTitle = $this->globalDataHelper->getValue('custom_title');
            return $customTitle !== null ? $customTitle : '';
        }

        $template = $this->globalDataHelper->getValue('ebay_template_synchronization');

        if ($template === null) {
            return '';
        }

        return $template->getTitle();
    }

    public function getFormData()
    {
        $template = $this->globalDataHelper->getValue('ebay_template_synchronization');

        if ($template === null || $template->getId() === null) {
            return [];
        }

        $data = array_merge($template->getData(), $template->getChildObject()->getData());

        return $data;
    }
}
