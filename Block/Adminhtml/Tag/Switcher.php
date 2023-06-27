<?php

namespace Ess\M2ePro\Block\Adminhtml\Tag;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Switcher
{
    public const TAG_ID_REQUEST_PARAM_KEY = 'tag';

    /** @var string */
    protected $paramName = self::TAG_ID_REQUEST_PARAM_KEY;
    /** @var \Ess\M2ePro\Model\Tag\ListingProduct\Repository */
    private $tagRelationRepository;
    /** @var string */
    private $label;
    /** @var string */
    private $componentMode;

    public function __construct(
        string $label,
        string $componentMode,
        string $controllerName,
        \Ess\M2ePro\Model\Tag\ListingProduct\Repository $tagRelationRepository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        /** @see parent::getSwitchUrl() */
        $this->setData('controller_name', $controllerName);

        $this->label = $label;
        $this->componentMode = $componentMode;
        $this->tagRelationRepository = $tagRelationRepository;
    }

    public function getLabel()
    {
        return $this->label;
    }

    protected function loadItems()
    {
        $tags = $this->tagRelationRepository->getTagEntitiesWithoutHasErrorsTag($this->componentMode);

        if (empty($tags)) {
            $this->items = [];

            return;
        }

        $items = [];
        foreach ($tags as $tag) {
            $items[$this->componentMode]['value'][] = [
                'value' => $tag->getId(),
                'label' => $tag->getErrorCode(),
            ];
        }

        $this->items = $items;
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
