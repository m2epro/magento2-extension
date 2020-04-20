<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Category;

use Magento\Backend\Block\Widget;
use Ess\M2ePro\Block\Adminhtml\Traits;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Category\AbstractCategory
 */
abstract class AbstractCategory extends \Magento\Catalog\Block\Adminhtml\Category\AbstractCategory
{
    use Traits\BlockTrait;
    use Traits\RendererTrait;

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    protected $parentFactory;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $blockContext,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\ResourceModel\Category\Tree $categoryTree,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
        $this->helperFactory = $blockContext->getHelperFactory();
        $this->modelFactory = $blockContext->getModelFactory();
        $this->activeRecordFactory = $blockContext->getActiveRecordFactory();
        $this->parentFactory = $blockContext->getParentFactory();

        $this->css = $blockContext->getCss();
        $this->jsPhp = $blockContext->getJsPhp();
        $this->js = $blockContext->getJs();
        $this->jsTranslator = $blockContext->getJsTranslator();
        $this->jsUrl = $blockContext->getJsUrl();

        parent::__construct($context, $categoryTree, $registry, $categoryFactory, $data);
    }
}
