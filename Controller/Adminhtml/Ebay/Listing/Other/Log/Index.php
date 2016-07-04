<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other\Log;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Log
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->ebayFactory->getObjectLoaded('Listing\Other', $id, 'id', false);

        if (is_null($model)) {
            $model = $this->ebayFactory->getObject('Listing\Other');
        }

        if (!$model->getId() && $id) {
            $this->getMessageManager()->addError($this->__('3rd Party Listing does not exist.'));
            return $this->_redirect('*/*/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('temp_data', $model->getData());

        if (!empty($id)) {
            $logBlock = $this->createBlock('Ebay\Listing\Other\Log');

            $this->getResult()->getConfig()->getTitle()->prepend($this->__(
                'Log For 3rd Party Listing "%s%"',
                $model->getChildObject()->getTitle()
            ));
        } else {

            // todo Remove when Mageto fix Horizontal Tabs bug
            if ($this->getRequest()->isXmlHttpRequest()) {
                return $this->_redirect('*/*/grid');
            }

            $logBlock = $this->createBlock('Ebay\Log\Tabs');
            $logBlock->setData('active_tab', \Ess\M2ePro\Block\Adminhtml\Ebay\Log\Tabs::TAB_ID_LISTING_OTHER);

            $this->getResult()->getConfig()->getTitle()->prepend($this->__('3rd Party Listings Logs & Actions'));
        }

        $this->addContent($logBlock);

        return $this->getResult();
    }

    //########################################
}