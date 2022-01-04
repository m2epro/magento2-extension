<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Transferring;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * @method \Ess\M2ePro\Model\Listing getListing()
 */
class Products extends AbstractBlock
{
    //########################################

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Transferring $transferring */
    protected $transferring;

    //########################################

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Model\Amazon\Listing\Transferring $transferring,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->transferring = $transferring;

        $this->addData($data);
        $this->setId('amazonListingTransferringProducts');
    }

    //########################################

    protected function _toHtml()
    {
        $translations = $this->getHelper('Data')->jsonEncode(
            [
                'Sell on Another Marketplace' => $this->__('Sell on Another Marketplace'),
                'Adding has been completed' => $this->__('Adding has been completed'),
                'Adding Products in process. Please wait...' => $this->__(
                    'Adding Products in process. Please wait...'
                )
            ]
        );

        $urls = $this->getHelper('Data')->jsonEncode(
            $this->getHelper('Data')->getControllerActions(
                'Amazon_Listing_Transferring',
                ['listing_id' => $this->getListing()->getId()]
            )
        );

        $this->transferring->setListing($this->getListing());

        $products = $this->getHelper('Data')->jsonEncode($this->transferring->getProductsIds());
        $successUrl = $this->getUrl(
            '*/amazon_listing/view',
            ['id' => $this->transferring->getTargetListingId()]
        );

        $this->js->add(
            <<<JS
    require([
        'domReady!',
        'M2ePro/M2ePro',
        'M2ePro/Amazon/Listing/Transferring'
    ], function() {

        M2ePro.translator.add({$translations});
        M2ePro.url.add({$urls});

        AmazonListingTransferringObj = new AmazonListingTransferring({$this->getListing()->getId()});
        AmazonListingTransferringObj.addProducts(
            'transferring_progress_bar',
            {$products},
            function() {
                window.location = '{$successUrl}';
            }
        );
    });
JS
        );

        return '<div id="transferring_progress_bar"></div>';
    }

    //########################################
}
