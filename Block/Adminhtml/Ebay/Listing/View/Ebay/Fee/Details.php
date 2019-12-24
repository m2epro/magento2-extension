<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay\Fee;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay\Fee\Details
 */
class Details extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $localeCurrency;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->localeCurrency = $localeCurrency;
        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingViewFeeDetails');
        // ---------------------------------------

        $this->setTemplate('ebay/listing/view/ebay/fee/details.phtml');
    }

    public function getFees()
    {
        if (empty($this->_data['fees']) || !is_array($this->_data['fees'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Fees are not set.');
        }

        $preparedData = [];

        foreach ($this->_data['fees'] as $feeName => $feeData) {
            if ($feeData['fee'] <= 0 && $feeName != 'listing_fee') {
                continue;
            }

            $camelCasedFeeName = str_replace('_', ' ', $feeName);
            $camelCasedFeeName = ucwords($camelCasedFeeName);

            $preparedData[$feeName] = [
                'label' => $camelCasedFeeName,
                'value' => $this->localeCurrency->getCurrency($feeData['currency'])->toCurrency($feeData['fee'])
            ];
        }

        return $preparedData;
    }

    //########################################
}
