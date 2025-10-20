<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml;

use Ess\M2ePro\Block\Adminhtml\Magento\Renderer\CssRenderer;
use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;
use Ess\M2ePro\Model\Wizard\VersionDowngrade;
use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

abstract class Base extends Action
{
    public const LAYOUT_ONE_COLUMN = '1column';
    public const LAYOUT_TWO_COLUMNS = '2columns';
    public const LAYOUT_BLANK = 'blank';

    public const MESSAGE_IDENTIFIER = 'm2epro_messages';
    public const GLOBAL_MESSAGES_GROUP = 'm2epro_global_messages_group';

    /** @var HelperFactory $helperFactory */
    protected $helperFactory;

    /** @var ModelFactory $modelFactory */
    protected $modelFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    protected $parentFactory;

    /** @var ActiveRecordFactory $activeRecordFactory */
    protected $activeRecordFactory;

    /** @var PageFactory $resultPageFactory */
    protected $resultPageFactory;

    /** @var \Magento\Framework\Controller\Result\RawFactory $resultRawFactory */
    protected $resultRawFactory;

    /** @var \Magento\Framework\View\LayoutFactory $layoutFactory */
    protected $layoutFactory;

    /** @var CssRenderer $cssRenderer */
    protected $cssRenderer;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Magento\Config\Model\Config */
    protected $magentoConfig;

    /** @var \Magento\Framework\Controller\Result\Raw $rawResult */
    protected $rawResult;

    /** @var \Magento\Framework\View\LayoutInterface $emptyLayout */
    protected $emptyLayout;

    /** @var \Magento\Framework\View\Result\Page $resultPage */
    protected $resultPage;

    /** @var \Magento\Framework\App\Response\RedirectInterface */
    protected $redirect;

    /** @var bool  */
    private $generalBlockWasAppended = false;

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
        $this->redirect = $context->getRedirect();

        parent::__construct($context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_auth->isLoggedIn();
    }

    //########################################

    protected function isAjax(?\Magento\Framework\App\RequestInterface $request = null)
    {
        if ($request === null) {
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
            self::MESSAGE_IDENTIFIER,
            ['content' => (string)$message],
            $group
        );
    }

    protected function addExtendedWarningMessage($message, $group = null)
    {
        $this->getMessageManager()->addComplexWarningMessage(
            self::MESSAGE_IDENTIFIER,
            ['content' => (string)$message],
            $group
        );
    }

    protected function addExtendedNoticeMessage($message, $group = null)
    {
        $this->getMessageManager()->addComplexNoticeMessage(
            self::MESSAGE_IDENTIFIER,
            ['content' => (string)$message],
            $group
        );
    }

    protected function addExtendedSuccessMessage($message, $group = null)
    {
        $this->getMessageManager()->addComplexSuccessMessage(
            self::MESSAGE_IDENTIFIER,
            ['content' => (string)$message],
            $group
        );
    }

    //########################################

    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if (($preDispatchResult = $this->preDispatch($request)) !== true) {
            return $preDispatchResult;
        }

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

            $params = [
                'error' => 'true',
            ];

            if ($this->getViewHelper()->getCurrentView() !== null) {
                $params['referrer'] = $this->getViewHelper()->getCurrentView();
            }

            return $this->_redirect($this->getHelper('Module\Support')->getPageRoute(), $params);
        }

        $this->postDispatch($request);

        return $result;
    }

    // ---------------------------------------

    protected function preDispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if ($this->getHelper('Module\Maintenance')->isEnabled()) {
            return $this->_redirect('*/maintenance');
        }

        /** @var \Ess\M2ePro\Helper\Module\Wizard $wizardHelper */
        $wizardHelper = $this->helperFactory->getObject('Module_Wizard');

        /** @var MigrationFromMagento1 $migrationFromMagento1Wizard */
        $migrationFromMagento1Wizard = $wizardHelper->getWizard(MigrationFromMagento1::NICK);
        if ($migrationFromMagento1Wizard->isUnexpectedlyCopiedFromM1()) {
            return $this->_redirect('*/migrationFromMagento1/initUnexpectedlyCopied');
        }

        /** @var VersionDowngrade $versionDowngradeWizard */
        $versionDowngradeWizard = $wizardHelper->getWizard(VersionDowngrade::NICK);
        if ($versionDowngradeWizard->isVersionDowngrade()) {
            $versionDowngradeWizard->startRepairProcess();

            return $this->_redirect('*/wizard_versionDowngrade/index');
        }

        if ($this->getHelper('Module')->isDisabled()) {
            $message = $this->__(
                'M2E Pro is disabled. Inventory and Order synchronization is not running.
                                  The Module interface is unavailable.<br>
                                  You can enable the Module under
                                  <i>Stores > Settings > Configuration > M2E Pro > Advanced Settings > Module</i>.'
            );
            $this->getMessageManager()->addNotice($message);

            return $this->_redirect('admin/dashboard');
        }

        if (empty($this->getHelper('Component')->getEnabledComponents())) {
            $message = $this->__(
                'Channel Integrations are disabled. To start working with M2E Pro, go to
                                  <i>Stores > Settings > Configuration > M2E Pro</i>
                                  and enable at least one Channel Integration.'
            );
            $this->getMessageManager()->addNotice($message);

            return $this->_redirect('admin/dashboard');
        }

        if ($this->isAjax($request) && !$this->_auth->isLoggedIn()) {
            $this->getRawResult()->setContents(
                \Ess\M2ePro\Helper\Json::encode([
                    'ajaxExpired' => 1,
                    'ajaxRedirect' => $this->redirect->getRefererUrl(),
                ])
            );

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
        if ($this->emptyLayout !== null) {
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
        if ($this->resultPage === null) {
            $this->initResultPage();
        }

        return $this->resultPage;
    }

    protected function initResultPage()
    {
        if ($this->resultPage !== null) {
            return;
        }

        $this->resultPage = $this->resultPageFactory->create();
        $this->resultPage->addHandle($this->getLayoutType());

        $this->resultPage->getConfig()->getTitle()->set($this->__('M2E Pro'));
    }

    // ---------------------------------------

    protected function getRawResult()
    {
        if ($this->rawResult === null) {
            $this->initRawResult();
        }

        return $this->rawResult;
    }

    protected function initRawResult()
    {
        if ($this->rawResult !== null) {
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

    protected function setRawContent($content)
    {
        return $this->getRawResult()->setContents($content);
    }

    protected function setAjaxContent($blockData, $appendGeneralBlock = true)
    {
        if ($blockData instanceof \Magento\Framework\View\Element\AbstractBlock) {
            $blockData = $blockData->toHtml();
        }

        if (!$this->generalBlockWasAppended && $appendGeneralBlock) {
            $generalBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\General::class);
            $generalBlock->setIsAjax(true);
            $blockData = $generalBlock->toHtml() . $blockData;
            $this->generalBlockWasAppended = true;
        }

        $this->getRawResult()->setContents($blockData);
    }

    /**
     * If key 'html' is exists, general block will be appended
     *
     * @param array $data
     */
    protected function setJsonContent(array $data)
    {
        if (!$this->generalBlockWasAppended && isset($data['html'])) {
            $generalBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\General::class);
            $generalBlock->setIsAjax(true);
            $data['html'] = $generalBlock->toHtml() . $data['html'];
            $this->generalBlockWasAppended = true;
        }

        $this->setAjaxContent(\Ess\M2ePro\Helper\Json::encode($data), false);
    }

    // ---------------------------------------

    protected function addCss($file)
    {
        $this->getResultPage()->getConfig()->addPageAsset("Ess_M2ePro::css/$file");
    }

    // ---------------------------------------

    protected function beforeAddLeftEvent()
    {
        return null;
    }

    protected function beforeAddContentEvent()
    {
        return null;
    }

    //########################################

    protected function appendGeneralBlock()
    {
        if ($this->generalBlockWasAppended) {
            return;
        }

        $generalBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\General::class);
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
     *
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
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function createBlock($block, $name = '', array $arguments = [])
    {
        // fix for Magento2 sniffs that forcing to use ::class
        $block = str_replace('_', '\\', $block);

        return $this->getLayout()->createBlock('Ess\M2ePro\Block\Adminhtml\\' . $block, $name, $arguments);
    }

    //########################################

    protected function getRequestIds($key = 'id')
    {
        $id = $this->getRequest()->getParam($key);
        $ids = $this->getRequest()->getParam($key . 's');

        if ($id === null && $ids === null) {
            return [];
        }

        $requestIds = [];

        if ($ids !== null) {
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
            $requestIds = (array)$ids;
        }

        if ($id !== null) {
            $requestIds[] = $id;
        }

        return array_filter($requestIds);
    }

    //########################################

    protected function setPageHelpLink($tinyLink)
    {
        /** @var \Magento\Theme\Block\Html\Title $pageTitleBlock */
        $pageTitleBlock = $this->getLayout()->getBlock('page.title');

        /** @var \Ess\M2ePro\Helper\Module\Support $supportHelper */
        $supportHelper = $this->getHelper('Module\Support');
        $helpLinkBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\PageHelpLink::class)->setData([
            'page_help_link' => $supportHelper->getDocumentationArticleUrl($tinyLink),
        ]);

        $pageTitleBlock->setTitleClass('m2epro-page-title');
        $pageTitleBlock->setChild('m2epro.page.help.block', $helpLinkBlock);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Helper\View
     */
    protected function getViewHelper(): \Ess\M2ePro\Helper\View
    {
        return $this->_objectManager->get(\Ess\M2ePro\Helper\View::class);
    }

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
