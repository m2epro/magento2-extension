<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

class ProductVariationVocabulary implements \Ess\M2ePro\Model\Servicing\TaskInterface
{
    public const NAME = 'product_variation_vocabulary';

    /** @var \Ess\M2ePro\Helper\Module\Product\Variation\Vocabulary */
    private $variationVocabulary;

    /**
     * @param \Ess\M2ePro\Helper\Module\Product\Variation\Vocabulary $variationVocabulary
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Product\Variation\Vocabulary $variationVocabulary
    ) {
        $this->variationVocabulary = $variationVocabulary;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getServerTaskName(): string
    {
        return self::NAME;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return true;
    }

    // ----------------------------------------

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        $metadata = $this->variationVocabulary->getServerMetaData();
        if (!isset($metadata['version'])) {
            $metadata['version'] = null;
        }

        return ['metadata' => $metadata];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function processResponseData(array $data): void
    {
        if (isset($data['data']) && is_array($data['data'])) {
            $this->variationVocabulary->setServerData($data['data']);
        }

        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $this->variationVocabulary->setServerMetadata($data['metadata']);
        }
    }
}
