<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add;

class Review extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /** @var \Ess\M2ePro\Model\Amazon\Search\Settings\CounterOfFind */
    private $counterOfFind;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Search\Settings\CounterOfFind $counterOfFind,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->globalDataHelper = $globalDataHelper;
        $this->counterOfFind = $counterOfFind;
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingProductReview');
        // ---------------------------------------

        $this->setTemplate('amazon/listing/product/add/review.phtml');
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------

        $listing = $this->globalDataHelper->getValue('listing_for_products_add');

        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            ['data' => ['listing' => $listing]]
        );

        $this->setChild('view_header', $viewHeaderBlock);

        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/*/viewListing', [
            '_current' => true,
            'id' => $this->getRequest()->getParam('id'),
        ]);

        $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                            ->setData([
                                'id' => __('go_to_the_listing'),
                                'label' => __('Go To The Listing'),
                                'onclick' => 'setLocation(\'' . $url . '\');',
                                'class' => 'action primary',
                            ]);
        $this->setChild('review', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/*/viewListingAndList', [
            '_current' => true,
            'id' => $this->getRequest()->getParam('id'),
        ]);

        $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                            ->setData([
                                'label' => __('List Added Products Now'),
                                'onclick' => 'setLocation(\'' . $url . '\');',
                                'class' => 'action primary',
                            ]);
        $this->setChild('list', $buttonBlock);
        // ---------------------------------------
    }

    /**
     * @return int
     */
    public function getCountOfFoundProducts(): int
    {
        return $this->counterOfFind->getCountAndReset();
    }
}
