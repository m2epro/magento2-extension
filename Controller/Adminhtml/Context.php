<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml;

use Magento\Backend\App\Action\Context as ActionContext;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory as ParentFactory;
use Ess\M2ePro\Helper\Factory as HelperFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Controller\Result\RawFactory;
use \Magento\Framework\View\LayoutFactory;
use Ess\M2ePro\Block\Adminhtml\Magento\Renderer\CssRenderer;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Context
 */
class Context extends ActionContext
{
    /** @var HelperFactory $helperFactory */
    protected $helperFactory = null;

    /** @var ModelFactory $modelFactory */
    protected $modelFactory = null;

    /** @var ParentFactory */
    protected $parentFactory = null;

    /** @var ActiveRecordFactory $activeRecordFactory */
    protected $activeRecordFactory = null;

    /** @var PageFactory $resultPageFactory */
    protected $resultPageFactory = null;

    /** @var \Magento\Framework\Controller\Result\RawFactory $resultRawFactory  */
    protected $resultRawFactory = null;

    /** @var \Magento\Framework\View\LayoutFactory $layoutFactory */
    protected $layoutFactory = null;

    /** @var CssRenderer|null  */
    protected $cssRenderer = null;

    /** @var \Magento\Framework\App\ResourceConnection|null  */
    protected $resourceConnection = null;

    /** @var \Magento\Config\Model\Config|null  */
    protected $magentoConfig = null;

    /** @var \Ess\M2ePro\Model\Setup\PublicVersionsChecker $publicVersionsChecker */
    private $publicVersionsChecker = null;

    public function __construct(
        CssRenderer $cssRenderer,
        ModelFactory $modelFactory,
        ParentFactory $parentFactory,
        ActiveRecordFactory $activeRecordFactory,
        HelperFactory $helperFactory,
        PageFactory $resultPageFactory,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\App\ViewInterface $view,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirectFactory,
        ResultFactory $resultFactory,
        \Magento\Backend\Model\Session $session,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Backend\Helper\Data $helper,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Config\Model\Config $magentoConfig,
        \Ess\M2ePro\Model\Setup\PublicVersionsChecker $publicVersionsChecker,
        $canUseBaseUrl = false
    ) {
        $this->cssRenderer = $cssRenderer;

        $this->modelFactory  = $modelFactory;
        $this->parentFactory  = $parentFactory;
        $this->activeRecordFactory  = $activeRecordFactory;
        $this->helperFactory = $helperFactory;
        $this->resultPageFactory = $resultPageFactory;

        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;

        $this->resourceConnection = $resourceConnection;

        $this->magentoConfig = $magentoConfig;

        $this->publicVersionsChecker = $publicVersionsChecker;

        parent::__construct(
            $request,
            $response,
            $objectManager,
            $eventManager,
            $url,
            $redirect,
            $actionFlag,
            $view,
            $messageManager,
            $resultRedirectFactory,
            $resultFactory,
            $session,
            $authorization,
            $auth,
            $helper,
            $backendUrl,
            $formKeyValidator,
            $localeResolver,
            $canUseBaseUrl
        );
    }

    /**
     * @return HelperFactory
     */
    public function getHelperFactory()
    {
        return $this->helperFactory;
    }

    /**
     * @return ModelFactory
     */
    public function getModelFactory()
    {
        return $this->modelFactory;
    }

    /**
     * @return ParentFactory
     */
    public function getParentFactory()
    {
        return $this->parentFactory;
    }

    /**
     * @return ActiveRecordFactory
     */
    public function getActiveRecordFactory()
    {
        return $this->activeRecordFactory;
    }

    /**
     * @return PageFactory
     */
    public function getResultPageFactory()
    {
        return $this->resultPageFactory;
    }

    /**
     * @return RawFactory
     */
    public function getResultRawFactory()
    {
        return $this->resultRawFactory;
    }

    /**
     * @return LayoutFactory
     */
    public function getLayoutFactory()
    {
        return $this->layoutFactory;
    }

    /**
     * @return CssRenderer|null
     */
    public function getCssRenderer()
    {
        return $this->cssRenderer;
    }

    /**
     * @return \Magento\Framework\App\ResourceConnection|null
     */
    public function getResourceConnection()
    {
        return $this->resourceConnection;
    }

    /**
     * @return \Magento\Config\Model\Config
     */
    public function getMagentoConfig()
    {
        return $this->magentoConfig;
    }

    /**
     * @return \Ess\M2ePro\Model\Setup\PublicVersionsChecker
     */
    public function getPublicVersionsChecker()
    {
        return $this->publicVersionsChecker;
    }
}
