<?php

namespace Ess\M2ePro\Controller\Adminhtml;

use \Magento\Backend\App\Action;

use \Ess\M2ePro\Model\Factory as ModelFactory;
use \Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use \Ess\M2ePro\Helper\Factory as HelperFactory;
use \Magento\Framework\View\Result\PageFactory;
use \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\CssRenderer;

abstract class Base extends Action
{
    const LAYOUT_ONE_COLUMN  = '1column';
    const LAYOUT_TWO_COLUMNS = '2columns';
    const LAYOUT_BLANK       = 'blank';

    const MESSAGE_IDENTIFIER    = 'm2epro_messages';
    const GLOBAL_MESSAGES_GROUP = 'm2epro_global_messages_group';

    /** @var HelperFactory $helperFactory */
    protected $helperFactory = NULL;

    /** @var ModelFactory $modelFactory */
    protected $modelFactory = NULL;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    protected $parentFactory = NULL;

    /** @var ActiveRecordFactory $activeRecordFactory */
    protected $activeRecordFactory = NULL;

    /** @var PageFactory $resultPageFactory */
    protected $resultPageFactory = NULL;

    /** @var \Magento\Framework\Controller\Result\RawFactory $resultRawFactory  */
    protected $resultRawFactory = NULL;

    /** @var \Magento\Framework\View\LayoutFactory $layoutFactory */
    protected $layoutFactory = NULL;

    /** @var CssRenderer $cssRenderer  */
    protected $cssRenderer = NULL;

    /** @var \Magento\Framework\App\ResourceConnection|null  */
    protected $resourceConnection = NULL;

    /** @var \Magento\Config\Model\Config */
    protected $magentoConfig = NULL;

    /** @var \Magento\Framework\Controller\Result\Raw $rawResult  */
    protected $rawResult = NULL;

    /** @var \Magento\Framework\View\LayoutInterface $emptyLayout */
    protected $emptyLayout = NULL;

    /** @var \Magento\Framework\View\Result\Page $resultPage  */
    protected $resultPage = NULL;

    /** @var \Ess\M2ePro\Model\Setup\PublicVersionsChecker $publicVersionsChecker */
    private $publicVersionsChecker = NULL;

    private $generalBlockWasAppended = false;

    //########################################

    public function __construct(Context $context)
    {
        $this->helperFactory = $context->getHelperFactory();
        $this->modelFactory = $context->getModelFactory();
        $this->parentFactory = $context->getParentFactory();
        $this->activeRecordFactory = $context->getActiveRecordFactory();
        $this->resultPageFactory = $context->getResultPageFactory();
        $this->resultRawFactory = $context->getResultRawFactory();
        $this->layoutFactory = $context->getLayoutFactory();
        $this->cssRenderer = $context->getCssRenderer();
        $this->resourceConnection = $context->getResourceConnection();
        $this->magentoConfig = $context->getMagentoConfig();
        $this->publicVersionsChecker = $context->getPublicVersionsChecker();

        parent::__construct($context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_auth->isLoggedIn();
    }

    //########################################

    protected function isAjax(\Magento\Framework\App\RequestInterface $request = NULL)
    {
        if (is_null($request)) {
            $request = $this->getRequest();
        }

        return $request->isXmlHttpRequest() || $request->getParam('isAjax');
    }

    //########################################

    protected function getLayoutType()
    {
        return self::LAYOUT_ONE_COLUMN;
    }

    //########################################

    public function getMessageManager()
    {
        return $this->messageManager;
    }

    // ---------------------------------------

    protected function addExtendedErrorMessage($message, $group = null)
    {
        $this->getMessageManager()->addComplexErrorMessage(
            self::MESSAGE_IDENTIFIER, ['content' => (string)$message], $group
        );
    }

    protected function addExtendedWarningMessage($message, $group = null)
    {
        $this->getMessageManager()->addComplexWarningMessage(
            self::MESSAGE_IDENTIFIER, ['content' => (string)$message], $group
        );
    }

    protected function addExtendedNoticeMessage($message, $group = null)
    {
        $this->getMessageManager()->addComplexNoticeMessage(
            self::MESSAGE_IDENTIFIER, ['content' => (string)$message], $group
        );
    }

    protected function addExtendedSuccessMessage($message, $group = null)
    {
        $this->getMessageManager()->addComplexSuccessMessage(
            self::MESSAGE_IDENTIFIER, ['content' => (string)$message], $group
        );
    }

    //########################################

    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if (($preDispatchResult = $this->preDispatch($request)) !== true) {
            return $preDispatchResult;
        }

        $this->publicVersionsChecker->doCheck();

        $this->getHelper('Module\Exception')->setFatalErrorHandler();

        try {

            $result = parent::dispatch($request);

        } catch (\Exception $exception) {

            if ($request->getControllerName() == $this->getHelper('Module\Support')->getPageControllerName()) {

                $this->getRawResult()->setContents($exception->getMessage());
                return $this->getRawResult();
            }

            if ($this->getHelper('Module')->isDevelopmentEnvironment()) {
                throw $exception;
            }

            $this->getHelper('Module\Exception')->process($exception);

            if ($request->isXmlHttpRequest() || $request->getParam('isAjax')) {
                $this->getRawResult()->setContents($exception->getMessage());
                return $this->getRawResult();
            }

            $this->getMessageManager()->addError(
                $this->getHelper('Module\Exception')->getUserMessage($exception)
            );

            $params = array(
                'error' => 'true'
            );

            if (!is_null($this->getHelper('View')->getCurrentView())) {
                $params['referrer'] = $this->getHelper('View')->getCurrentView();
            }

            return $this->_redirect($this->getHelper('Module\Support')->getPageRoute(), $params);
        }

        $this->postDispatch($request);

        return $result;
    }

    // ---------------------------------------

    protected function preDispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if ($this->getHelper('Module\Maintenance\General')->isEnabled()) {
            return $this->_redirect('*/maintenance');
        }

        if (empty($this->getHelper('Component')->getEnabledComponents()) ||
            $this->getHelper('Module')->isDisabled()) {

            return $this->_redirect('admin/dashboard');
        }

        if ($this->isAjax($request) && !$this->_auth->isLoggedIn()) {
            $this->getRawResult()->setContents($this->getHelper('Data')->jsonEncode(array(
                'ajaxExpired'  => 1,
                'ajaxRedirect' => $this->_redirect->getRefererUrl()
            )));

            return $this->getRawResult();
        }

        return true;
    }

    protected function postDispatch(\Magento\Framework\App\RequestInterface $request)
    {
        ob_get_clean();

        if ($this->isAjax($request)) {
            return;
        }

        if ($this->getLayoutType() == self::LAYOUT_BLANK) {
            $this->addCss('layout/blank.css');
        }

        foreach ($this->cssRenderer->getFiles() as $file) {
            $this->addCss($file);
        }
    }

    //########################################

    protected function getLayout()
    {
        if ($this->isAjax()) {
            $this->initEmptyLayout();
            return $this->emptyLayout;
        }

        return $this->getResultPage()->getLayout();
    }

    protected function initEmptyLayout()
    {
        if (!is_null($this->emptyLayout)) {
            return;
        }

        $this->emptyLayout = $this->layoutFactory->create();
    }

    // ---------------------------------------

    protected function getResult()
    {
        if ($this->isAjax()) {
            return $this->getRawResult();
        }

        return $this->getResultPage();
    }

    // ---------------------------------------

    protected function getResultPage()
    {
        if (is_null($this->resultPage)) {
            $this->initResultPage();
        }

        return $this->resultPage;
    }

    protected function initResultPage()
    {
        if (!is_null($this->resultPage)) {
            return;
        }

        $this->resultPage = $this->resultPageFactory->create();
        $this->resultPage->addHandle($this->getLayoutType());

        $this->resultPage->getConfig()->getTitle()->set($this->__('M2E Pro'));
    }

    // ---------------------------------------

    protected function getRawResult()
    {
        if (is_null($this->rawResult)) {
            $this->initRawResult();
        }

        return $this->rawResult;
    }

    protected function initRawResult()
    {
        if (!is_null($this->rawResult)) {
            return;
        }

        $this->rawResult = $this->resultRawFactory->create();
    }

    //########################################

    protected function addLeft(\Magento\Framework\View\Element\AbstractBlock $block)
    {
        if ($this->getLayoutType() != self::LAYOUT_TWO_COLUMNS) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Add left can not be used for non two column layout');
        }

        $this->initResultPage();
        $this->beforeAddLeftEvent();
        $this->appendGeneralBlock();

        return $this->_addLeft($block);
    }

    protected function addContent(\Magento\Framework\View\Element\AbstractBlock $block)
    {
        $this->initResultPage();
        $this->beforeAddContentEvent();
        $this->appendGeneralBlock();

        return $this->_addContent($block);
    }

    protected function setAjaxContent($blockData, $appendGeneralBlock = true)
    {
        if ($blockData instanceof \Magento\Framework\View\Element\AbstractBlock) {
            $blockData = $blockData->toHtml();
        }

        if (!$this->generalBlockWasAppended && $appendGeneralBlock) {
            $generalBlock = $this->createBlock(\Ess\M2ePro\Helper\View::GENERAL_BLOCK_PATH);
            $generalBlock->setIsAjax(true);
            $blockData = $generalBlock->toHtml() . $blockData;
            $this->generalBlockWasAppended = true;
        }

        $this->getRawResult()->setContents($blockData);
    }

    /**
     * If key 'html' is exists, general block will be appended
     * @param array $data
     */
    protected function setJsonContent(array $data)
    {
        if (!$this->generalBlockWasAppended && isset($data['html'])) {
            $generalBlock = $this->createBlock(\Ess\M2ePro\Helper\View::GENERAL_BLOCK_PATH);
            $generalBlock->setIsAjax(true);
            $data['html'] = $generalBlock->toHtml() . $data['html'];
            $this->generalBlockWasAppended = true;
        }

        $this->setAjaxContent($this->getHelper('Data')->jsonEncode($data), false);
    }

    // ---------------------------------------

    protected function addCss($file)
    {
        $this->getResultPage()->getConfig()->addPageAsset("Ess_M2ePro::css/$file");
    }

    // ---------------------------------------

    protected function beforeAddLeftEvent() {}

    protected function beforeAddContentEvent() {}

    //########################################

    protected function appendGeneralBlock()
    {
        if ($this->generalBlockWasAppended) {
            return;
        }

        $generalBlock = $this->createBlock(\Ess\M2ePro\Helper\View::GENERAL_BLOCK_PATH);
        $this->getLayout()->setChild('js', $generalBlock->getNameInLayout(), '');

        $this->generalBlockWasAppended = true;
    }

    //########################################

    protected function __()
    {
        return $this->getHelper('Module\Translation')->translate(func_get_args());
    }

    /**
     * @param $helperName
     * @param array $arguments
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getHelper($helperName, array $arguments = [])
    {
        return $this->helperFactory->getObject($helperName, $arguments);
    }

    /**
     * @param $block
     * @param $name
     * @param $arguments
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function createBlock($block, $name = '', array $arguments = [])
    {
        return $this->getLayout()->createBlock('Ess\M2ePro\Block\Adminhtml\\' . $block, $name, $arguments);
    }

    //########################################

    protected function getRequestIds($key = 'id')
    {
        $id = $this->getRequest()->getParam($key);
        $ids = $this->getRequest()->getParam($key.'s');

        if (is_null($id) && is_null($ids)) {
            return array();
        }

        $requestIds = array();

        if (!is_null($ids)) {
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
            $requestIds = (array)$ids;
        }

        if (!is_null($id)) {
            $requestIds[] = $id;
        }

        return array_filter($requestIds);
    }

    //########################################

    protected function setPageHelpLink($tinyLink)
    {
        /** @var \Magento\Theme\Block\Html\Title $pageTitleBlock */
        $pageTitleBlock = $this->getLayout()->getBlock('page.title');

        $helpLinkBlock = $this->createBlock('PageHelpLink')->setData([
            'page_help_link' => $this->getHelper('Module\Support')->getDocumentationArticleUrl(
                $tinyLink
            )
        ]);

        $pageTitleBlock->setTitleClass('m2epro-page-title');
        $pageTitleBlock->setChild('m2epro.page.help.block', $helpLinkBlock);
    }

    //########################################

    /**
     * Clears global messages session to prevent duplicate
     * @inheritdoc
     */
    protected function _redirect($path, $arguments = [])
    {
        $this->messageManager->getMessages(true, self::GLOBAL_MESSAGES_GROUP);
        return parent::_redirect($path, $arguments);
    }

    //########################################
}