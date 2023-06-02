<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class SearchByKeywords extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType\SuggestManager */
    private $suggestManager;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductType\SuggestManager $suggestManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->suggestManager = $suggestManager;
    }

    public function execute()
    {
        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        if (!$marketplaceId) {
            $this->setJsonContent([
                'result' => false,
                'message' => 'You should provide correct marketplace_id.',
            ]);

            return $this->getResult();
        }

        $keywords = $this->prepareKeywords(
            (string)$this->getRequest()->getParam('keywords')
        );
        $productTypes = $this->suggestManager->getProductTypes($marketplaceId, $keywords);

        $this->setJsonContent([
            'result' => true,
            'data' => $productTypes,
        ]);

        return $this->getResult();
    }

    private function prepareKeywords(string $keywords): array
    {
        $result = explode(',', $keywords);
        $result = array_map(
            static function ($item) {
                return trim($item);
            },
            $result
        );

        return array_filter($result);
    }
}
