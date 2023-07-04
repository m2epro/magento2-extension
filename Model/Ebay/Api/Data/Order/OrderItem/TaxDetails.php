<?php

namespace Ess\M2ePro\Model\Ebay\Api\Data\Order\OrderItem;

class TaxDetails extends \Ess\M2ePro\Api\DataObject implements
    \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetailsInterface
{
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetails\CollectAndRemitInterfaceFactory */
    private $collectAndRemitFactory;

    public function __construct(
        \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetails\CollectAndRemitInterfaceFactory $collectAndRemitFactory,
        array $data = []
    ) {
        parent::__construct($data);
        $this->collectAndRemitFactory = $collectAndRemitFactory;
    }

    public function getRate(): float
    {
        return (float)$this->getData(self::RATE_KEY);
    }

    public function getAmount(): float
    {
        return (float)$this->getData(self::AMOUNT_KEY);
    }

    public function getEbayCollectTaxes(): ?string
    {
        return $this->getData(self::EBAY_COLLECT_TAXES_KEY);
    }

    public function getCollectAndRemit(): \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetails\CollectAndRemitInterface
    {
        $collectAndRemit = $this->collectAndRemitFactory->create();
        $collectAndRemit->addData($this->getDecodedJsonData(self::COLLECT_AND_REMIT));

        return $collectAndRemit;
    }
}
