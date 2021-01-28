<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add\SaveSearchSettings
 */
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
        $listing = $this->amazonFactory->getObjectLoaded('Listing', $post['id'])->getChildObject();

        $listing->setData('general_id_mode', $post['general_id_mode']);
        $listing->setData('general_id_custom_attribute', $post['general_id_custom_attribute']);
        $listing->setData('worldwide_id_mode', $post['worldwide_id_mode']);
        $listing->setData('worldwide_id_custom_attribute', $post['worldwide_id_custom_attribute']);
        $listing->setData('search_by_magento_title_mode', $post['search_by_magento_title_mode']);

        $listing->save();
    }

    //########################################
}
