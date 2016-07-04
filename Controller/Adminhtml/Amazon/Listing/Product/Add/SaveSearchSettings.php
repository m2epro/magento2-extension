<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class SaveSearchSettings extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (empty($post['id'])) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing $listingProduct */
        $amazonListing = $this->amazonFactory->getObjectLoaded('Listing', $post['id'])->getChildObject();

        $amazonListing->setData('general_id_mode',                 $post['general_id_mode']);
        $amazonListing->setData('general_id_custom_attribute',     $post['general_id_custom_attribute']);
        $amazonListing->setData('worldwide_id_mode',               $post['worldwide_id_mode']);
        $amazonListing->setData('worldwide_id_custom_attribute',   $post['worldwide_id_custom_attribute']);
        $amazonListing->setData('search_by_magento_title_mode',    $post['search_by_magento_title_mode']);

        $amazonListing->save();
    }

    //########################################
}