<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form\PriceTable
 */
class PriceTable extends AbstractBlock
{
    protected $_template = 'ebay/template/selling_format/price_table.phtml';

    protected $currency;

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $currency,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->currency = $currency;
        parent::__construct($context, $data);
    }

    public function getCurrencySymbol($currency)
    {
        return $this->currency->getCurrency($currency)->getSymbol();
    }
}
