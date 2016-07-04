<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other\Log;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Log
{
    //########################################

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->activeRecordFactory->getObjectLoaded('Listing\Other', $id, 'id', false);

        if (is_null($model)) {
            $model = $this->activeRecordFactory->getObject('Listing\Other');
        }

        if (!$model->getId() && $id) {
            $this->getMessageManager()->addError($this->__('3rd Party Listing does not exist.'));
            return $this->_redirect('*/amazon_listing_log/index');
        }

        $this->getHelper('Data\GlobalData')->setValue('temp_data', $model->getData());

        if ($model->getId()) {
            $block = $this->createBlock('Amazon\Listing\Other\Log');

            $this->getResult()->getConfig()->getTitle()->prepend($this->__(
                'Log For 3rd Party Listing "%s%"',
                $model->getChildObject()->getTitle()
            ));
        } else {

            // todo Remove when Mageto fix Horizontal Tabs bug
            if ($this->getRequest()->isXmlHttpRequest()) {
                return $this->_redirect('*/*/grid', ['_current' => true]);
            }

            $block = $this->createBlock('Amazon\Log\Tabs');
            $block->setData('active_tab', \Ess\M2ePro\Block\Adminhtml\Amazon\Log\Tabs::TAB_ID_LISTING_OTHER);

            $this->resultPage->getConfig()->getTitle()->prepend($this->__('3rd Party Listings Logs & Actions'));
        }

        $this->addContent($block);

        return $this->resultPage;
    }

    //########################################
}