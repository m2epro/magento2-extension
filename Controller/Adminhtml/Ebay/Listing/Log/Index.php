<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Log;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Log
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id', false);
        if ($id) {
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id);

            if (!$listing->getId()) {
                $this->getMessageManager()->addError($this->__('Listing does not exist.'));
                return $this->_redirect('*/*/index');
            }

            $logBlock = $this->createBlock('Ebay\Listing\Log');

            $this->getResult()->getConfig()->getTitle()->prepend(
                $this->__('M2E Pro Listing "%s%" Log', $listing->getTitle())
            );
        } else {

            // todo Remove when Mageto fix Horizontal Tabs bug
            if ($this->getRequest()->isXmlHttpRequest()) {
                return $this->_redirect('*/*/grid');
            }

            $logBlock = $this->createBlock('Ebay\Log\Tabs', '');
            $logBlock->setData('active_tab', \Ess\M2ePro\Block\Adminhtml\Ebay\Log\Tabs::TAB_ID_LISTING);

            $this->getResult()->getConfig()->getTitle()->prepend($this->__('Listings Logs & Events'));
        }

        $this->addContent($logBlock);

        return $this->getResult();
    }

    //########################################
}