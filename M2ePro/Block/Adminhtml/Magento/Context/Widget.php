<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Context;

use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Block\Widget\Context;
use Ess\M2ePro\Block\Adminhtml\Traits;
use Ess\M2ePro\Block\Adminhtml\Magento\Renderer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget
 */
class Widget extends Context
{
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
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        Renderer\CssRenderer $css,
        Renderer\JsPhpRenderer $jsPhp,
        Renderer\JsRenderer $js,
        Renderer\JsTranslatorRenderer $jsTranslatorRenderer,
        Renderer\JsUrlRenderer $jsUrlRenderer,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Framework\Session\Generic $session,
        \Magento\Framework\Session\SidResolverInterface $sidResolver,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\View\ConfigInterface $viewConfig,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\View\FileSystem $viewFileSystem,
        \Magento\Framework\View\TemplateEnginePool $enginePool,
        \Magento\Framework\App\State $appState,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Page\Config $pageConfig,
        \Magento\Framework\View\Element\Template\File\Resolver $resolver,
        \Magento\Framework\View\Element\Template\File\Validator $validator,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Backend\Model\Session $backendSession,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        Button\ButtonList $buttonList,
        Button\ToolbarInterface $toolbar
    ) {
        $this->helperFactory = $helperFactory;
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->parentFactory = $parentFactory;

        $this->css = $css;
        $this->jsPhp = $jsPhp;
        $this->js = $js;
        $this->jsTranslator = $jsTranslatorRenderer;
        $this->jsUrl = $jsUrlRenderer;

        parent::__construct(
            $request,
            $layout,
            $eventManager,
            $urlBuilder,
            $cache,
            $design,
            $session,
            $sidResolver,
            $scopeConfig,
            $assetRepo,
            $viewConfig,
            $cacheState,
            $logger,
            $escaper,
            $filterManager,
            $localeDate,
            $inlineTranslation,
            $filesystem,
            $viewFileSystem,
            $enginePool,
            $appState,
            $storeManager,
            $pageConfig,
            $resolver,
            $validator,
            $authorization,
            $backendSession,
            $mathRandom,
            $formKey,
            $nameBuilder,
            $buttonList,
            $toolbar
        );
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsPhpRenderer
     */
    public function getJsPhp()
    {
        return $this->jsPhp;
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsTranslatorRenderer
     */
    public function getJsTranslator()
    {
        return $this->jsTranslator;
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsUrlRenderer
     */
    public function getJsUrl()
    {
        return $this->jsUrl;
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsRenderer
     */
    public function getJs()
    {
        return $this->js;
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\CssRenderer
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @return \Ess\M2ePro\Helper\Factory
     */
    public function getHelperFactory()
    {
        return $this->helperFactory;
    }

    /**
     * @return \Ess\M2ePro\Model\Factory
     */
    public function getModelFactory()
    {
        return $this->modelFactory;
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Factory
     */
    public function getActiveRecordFactory()
    {
        return $this->activeRecordFactory;
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory
     */
    public function getParentFactory()
    {
        return $this->parentFactory;
    }
}
