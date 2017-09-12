<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\InterfaceTab;

class RestoreRememberedChoices extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    public function execute()
    {
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();

        foreach ($collection as $listing) {
            /** @var $listing \Ess\M2ePro\Model\Listing */

            $additionalData = $listing->getSettings('additional_data');

            if ($listing->isComponentModeEbay()) {
                unset($additionalData['show_settings_step']);
                unset($additionalData['mode_same_category_data']);
            }

            if ($listing->isComponentModeAmazon()) {
                unset($additionalData['show_new_asin_step']);
            }

            $listing->setSettings('additional_data', $additionalData);
            $listing->save();
        }

        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }

    //########################################
}