<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Log;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Log
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id', false);
        if ($id) {
            $listing = $this->activeRecordFactory->getObjectLoaded('Listing', $id, 'id', false);

            if (is_null($listing)) {
                $listing = $this->activeRecordFactory->getObject('Listing');
            }

            if (!$listing->getId()) {
                $this->getMessageManager()->addError($this->__('Listing does not exist.'));
                return $this->_redirect('*/*/index');
            }

            $block = $this->createBlock('Amazon\Listing\Log');

            $this->getResult()->getConfig()->getTitle()->prepend(
                $this->__('Logs For Listing "%s%"', $listing->getTitle())
            );
        } else {

            // todo Remove when Mageto fix Horizontal Tabs bug
            if ($this->getRequest()->isXmlHttpRequest()) {
                return $this->_redirect('*/*/grid', ['_current' => true]);
            }

            $block = $this->createBlock('Amazon\Log\Tabs');
            $block->setData('active_tab', \Ess\M2ePro\Block\Adminhtml\Amazon\Log\Tabs::TAB_ID_LISTING);

            $this->getResult()->getConfig()->getTitle()->prepend($this->__('Listings Logs & Events'));
        }

        $this->addContent($block);

        return $this->getResult();
    }

    //########################################
}