<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Helper\Data\Session;

class Review extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    public function __construct(
        Session $sessionHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->globalData = $globalData;
        $this->dataHelper = $dataHelper;
        $this->sessionHelper = $sessionHelper;
    }

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);

        $this->globalData->setValue('review_listing', $listing);
        $ids = $this->sessionHelper->getValue('added_products_ids');

        if (empty($ids) && !$this->getRequest()->getParam('disable_list')) {
            return $this->_redirect('*/*/view', ['id' => $listingId]);
        }
        $blockReview = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Review::class,
            '',
            [
            'data' => [
                'products_count' => count($ids)
            ]
            ]
        );

        $additionalData = $listing->getSettings('additional_data');

        if (isset($additionalData['source']) && $source = $additionalData['source']) {
            $blockReview->setSource($source);
        }

        unset($additionalData['source']);
        $listing->setSettings('additional_data', $additionalData);
        $listing->getChildObject()->setData('product_add_ids', $this->dataHelper->jsonEncode([]));
        $listing->getChildObject()->save();
        $listing->save();

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Congratulations'));
        $this->addContent($blockReview);

        return $this->getResult();
    }
}
