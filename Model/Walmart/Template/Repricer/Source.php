<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template\Repricer;

class Source extends \Ess\M2ePro\Model\AbstractModel
{
    private \Ess\M2ePro\Model\Walmart\Template\Repricer $templateModel;
    private \Ess\M2ePro\Model\Magento\Product $magentoProduct;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Template\Repricer $templateModel,
        \Ess\M2ePro\Model\Magento\Product $magentoProduct,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->templateModel = $templateModel;
        $this->magentoProduct = $magentoProduct;
    }

    public function getStrategyName(): ?string
    {
        return $this->templateModel->getStrategyName();
    }

    public function getRepricerMinPrice(): ?float
    {
        if ($this->templateModel->isMinPriceModeAttribute()) {
            $result = $this->magentoProduct
                ->getAttributeValue($this->templateModel->getMinPriceAttribute());

            if (empty($result)) {
                return null;
            }

            return (float)$result;
        }

        return null;
    }

    public function getRepricerMaxPrice(): ?float
    {
        if ($this->templateModel->isMaxPriceModeAttribute()) {
            $result = $this->magentoProduct
                ->getAttributeValue($this->templateModel->getMaxPriceAttribute());

            if (empty($result)) {
                return null;
            }

            return (float)$result;
        }

        return null;
    }
}
