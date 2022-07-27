<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;
use \Magento\Backend\App\Action;

class GetTemplateHtml extends Template
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Template\Switcher\DataLoader */
    private $componentEbayTemplateSwitcherDataLoader;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Template\Switcher\DataLoader $componentEbayTemplateSwitcherDataLoader,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($templateManager, $ebayFactory, $context);

        $this->componentEbayTemplateSwitcherDataLoader = $componentEbayTemplateSwitcherDataLoader;
    }

    public function execute()
    {
        try {
            // ---------------------------------------
            /** @var \Ess\M2ePro\Helper\Component\Ebay\Template\Switcher\DataLoader $dataLoader */
            $dataLoader = $this->componentEbayTemplateSwitcherDataLoader;
            $dataLoader->load($this->getRequest());
            // ---------------------------------------

            // ---------------------------------------
            $templateNick = $this->getRequest()->getParam('nick');
            $templateDataForce = (bool)$this->getRequest()->getParam('data_force', false);

            /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher $switcherBlock */
            $switcherBlock = $this->getLayout()
                                  ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher::class);
            $switcherBlock->setData(['template_nick' => $templateNick]);
            // ---------------------------------------

            $this->setAjaxContent($switcherBlock->getFormDataBlockHtml($templateDataForce));
        } catch (\Exception $e) {
            $this->setJsonContent(['error' => $e->getMessage()]);
        }

        return $this->getResult();
    }
}
