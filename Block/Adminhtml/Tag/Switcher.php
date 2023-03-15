<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Tag;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Switcher
{
    public const TAG_ID_REQUEST_PARAM_KEY = 'tag';

    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\CollectionFactory */
    private $collectionFactory;
    /** @var string */
    protected $paramName = self::TAG_ID_REQUEST_PARAM_KEY;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Tag\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheridoc
     */
    public function getLabel()
    {
        return __('eBay Error');
    }

    /**
     * @inheridoc
     */
    protected function loadItems()
    {
        $collection = $this->collectionFactory->create();
        $tags = $collection->getItemsWithoutHasErrorsTag();

        if (empty($tags)) {
            $this->items = [];

            return;
        }

        $items = [];
        foreach ($tags as $tag) {
            $items[$this->getComponentMode()]['value'][] = [
                'value' => $tag->getId(),
                'label' => $tag->getErrorCode(),
            ];
        }

        $this->items = $items;
    }

    /**
     * @return string
     */
    public function getComponentMode(): string
    {
        return $this->getData('component_mode');
    }

    /**
     * @return string
     */
    public function getDefaultOptionName()
    {
        return ' ';
    }

    /**
     * @return true
     */
    public function hasDefaultOption()
    {
        return true;
    }
}
