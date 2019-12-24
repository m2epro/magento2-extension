<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Save
 */
class Save extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing
{
    protected $dateTime;

    //########################################

    public function __construct(
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->dateTime = $dateTime;
        parent::__construct($walmartFactory, $context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        if (!$post = $this->getRequest()->getPost()) {
            $this->_redirect('*/walmart_listing/index');
        }

        $id = $this->getRequest()->getParam('id');
        $listing = $this->walmartFactory->getObjectLoaded('Listing', $id, null, false);

        if ($listing === null && $id) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/walmart_listing/index');
        }

        $oldData = array_merge($listing->getDataSnapshot(), $listing->getChildObject()->getDataSnapshot());

        // Base prepare
        // ---------------------------------------
        $data = [];
        // ---------------------------------------

        // tab: settings
        // ---------------------------------------
        $keys = [
            'template_description_id',
            'template_selling_format_id',
            'template_synchronization_id',
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        $listing->addData($data);
        $listing->getChildObject()->addData($data);
        $listing->save();

        $templateData = [];

        // tab: channel settings
        // ---------------------------------------
        $keys = [
            'account_id',
            'marketplace_id',
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $templateData[$key] = $post[$key];
            }
        }
        // ---------------------------------------

        $listing->addData($templateData);
        $listing->getChildObject()->addData($templateData);
        $listing->save();

        $newData = array_merge($listing->getDataSnapshot(), $listing->getChildObject()->getDataSnapshot());

        $listing->getChildObject()->setSynchStatusNeed($newData, $oldData);

        $this->getMessageManager()->addSuccess($this->__('The Listing was successfully saved.'));

        return $this->_redirect($this->getHelper('Data')->getBackUrl('list', [], ['edit'=>['id'=>$id]]));
    }

    //########################################
}
