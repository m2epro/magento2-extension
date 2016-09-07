<?php

namespace Ess\M2ePro\Controller\Adminhtml\Maintenance;

use \Magento\Backend\App\Action;
use \Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    private $pageFactory;

    private $maintenanceHelper;

    //########################################

    public function __construct(
        PageFactory $pageFactory,
        \Ess\M2ePro\Helper\Module\Maintenance\General $maintenanceHelper,
        Action\Context $context
    ) {
        $this->pageFactory = $pageFactory;
        $this->maintenanceHelper = $maintenanceHelper;
        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        if (!$this->maintenanceHelper->isEnabled()) {
            return $this->_redirect('admin');
        }

        $result = $this->pageFactory->create();

        $result->getConfig()->getTitle()->set(__(
            'M2E Pro is currently in a maintenance mode (Module is not working now)'
        ));
        $this->_setActiveMenu('Ess_M2ePro::m2epro_maintenance');

        /** @var \Magento\Framework\View\Element\Template $block */
        $block = $result->getLayout()->createBlock('\Magento\Framework\View\Element\Template');
        $block->setTemplate('Ess_M2ePro::maintenance.phtml');

        $this->_addContent($block);

        return $result;
    }

    //########################################
}