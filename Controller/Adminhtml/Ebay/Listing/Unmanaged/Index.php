<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Unmanaged;

use Ess\M2ePro\Model\Ebay\Listing\Wizard;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Repository as WizardRepository;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Controller\Adminhtml\Context;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    private WizardRepository $wizardRepository;

    public function __construct(
        WizardRepository $wizardRepository,
        Factory $ebayFactory,
        Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->wizardRepository = $wizardRepository;
    }

    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Unmanaged\Grid::class)
            );

            return $this->getResult();
        }

        $existWizard = $this->wizardRepository->findNotCompletedWizardByType(Wizard::TYPE_UNMANAGED);

        if ($existWizard !== null && !$existWizard->isCompleted()) {
            $this->getMessageManager()->addNotice(
                $this->__(
                    'Please make sure you finish adding new Products before moving to the next step.'
                )
            );

            return $this->_redirect('*/ebay_listing_wizard/index', ['id' => $existWizard->getId()]);
        }

        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Unmanaged::class)
        );
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('All Unmanaged Items'));
        $this->setPageHelpLink('the-unmanaged-listings');

        return $this->getResult();
    }
}
