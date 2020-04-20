<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View\ControlPanel;

use Ess\M2ePro\Helper\Factory;
use Ess\M2ePro\Helper\Module;

/**
 * Class \Ess\M2ePro\Helper\View\ControlPanel\Command
 */
class Command extends \Ess\M2ePro\Helper\AbstractHelper
{
    //########################################

    const CONTROLLER_MODULE_INTEGRATION         = 'controlPanel_module/integration';
    const CONTROLLER_MODULE_INTEGRATION_EBAY    = 'controlPanel_module_integration/ebay';
    const CONTROLLER_MODULE_INTEGRATION_AMAZON  = 'controlPanel_module_integration/amazon';
    const CONTROLLER_MODULE_INTEGRATION_WALMART = 'controlPanel_module_integration/walmart';

    const CONTROLLER_TOOLS_M2EPRO_GENERAL   = 'controlPanel_tools_m2ePro/general';
    const CONTROLLER_TOOLS_M2EPRO_INSTALL   = 'controlPanel_tools_m2ePro/install';
    const CONTROLLER_TOOLS_MAGENTO          = 'controlPanel_tools/magento';
    const CONTROLLER_TOOLS_ADDITIONAL       = 'controlPanel_tools/additional';

    private $backendUrlBuilder;

    //########################################

    public function __construct(
        \Magento\Backend\Model\Url $backendUrlBuilder,
        Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->backendUrlBuilder = $backendUrlBuilder;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function parseGeneralCommandsData($controller)
    {
        $tempClass = $this->getControllerClassName($controller);

        $reflectionClass = new \ReflectionClass($tempClass);
        $reflectionMethods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Get actions methods
        // ---------------------------------------
        $actions = [];
        foreach ($reflectionMethods as $reflectionMethod) {
            $methodName = $reflectionMethod->name;

            if (substr($methodName, strlen($methodName)-6) != 'Action') {
                continue;
            }

            $methodName = substr($methodName, 0, strlen($methodName)-6);

            $actions[] = $methodName;
        }
        // ---------------------------------------

        // Print method actions
        // ---------------------------------------
        $methods = [];
        foreach ($actions as $action) {
            $controllerName = $this->getControllerClassName($controller);
            $reflectionMethod = new \ReflectionMethod($controllerName, $action.'Action');

            $commentsString = $this->getMethodComments($reflectionMethod);

            preg_match('/@hidden/', $commentsString, $matches);
            if (isset($matches[0])) {
                continue;
            }

            $methodInvisible = false;
            preg_match('/@invisible/', $commentsString, $matches);
            isset($matches[0]) && $methodInvisible = true;

            $methodTitle = $action;
            preg_match('/@title[\s]*\"(.*)\"/', $commentsString, $matches);
            isset($matches[1]) && $methodTitle = $matches[1];

            $methodDescription = '';
            preg_match('/@description[\s]*\"(.*)\"/', $commentsString, $matches);
            isset($matches[1]) && $methodDescription = $matches[1];

            $methodContent = '';
            $fileContent = file($reflectionMethod->getFileName());
            for ($i = $reflectionMethod->getStartLine() + 2; $i < $reflectionMethod->getEndLine(); $i++) {
                $methodContent .= $fileContent[$i-1];
            }

            $methodNewLine = false;
            preg_match('/@new_line/', $commentsString, $matches);
            isset($matches[0]) && $methodNewLine = true;

            $methodConfirm = false;
            preg_match('/@confirm[\s]*\"(.*)\"/', $commentsString, $matches);
            isset($matches[1]) && $methodConfirm = $matches[1];

            $methodPrompt = false;
            preg_match('/@prompt[\s]*\"(.*)\"/', $commentsString, $matches);
            isset($matches[1]) && $methodPrompt = $matches[1];

            $methodPromptVar = '';
            preg_match('/@prompt_var[\s]*\"(.*)\"/', $commentsString, $matches);
            isset($matches[1]) && $methodPromptVar = $matches[1];

            $methodComponents = false;
            preg_match('/@components[ ]*(.*)/', $commentsString, $matches);
            isset($matches[0]) && $methodComponents = true;
            !empty($matches[1]) && $methodComponents = explode(',', $matches[1]);

            $methodNewWindow = false;
            preg_match('/new_window/', $commentsString, $matches);
            isset($matches[0]) && $methodNewWindow = true;

            $methods[] = [
                'invisible'      => $methodInvisible,
                'title'          => $methodTitle,
                'description'    => $methodDescription,
                'url'            => $this->backendUrlBuilder->getUrl('*/'.$controller, ['action' => $action]),
                'content'        => $methodContent,
                'new_line'       => $methodNewLine,
                'confirm'        => $methodConfirm,
                'prompt'      => [
                    'text' => $methodPrompt,
                    'var'  => $methodPromptVar
                ],
                'components'  => $methodComponents,
                'new_window'  => $methodNewWindow
            ];
        }
        // ---------------------------------------

        return $methods;
    }

    //########################################

    public function getControllerClassName($controller)
    {
        $controller = str_replace(['_', '/'], '\\', $controller);

        $controller = array_map(function ($part) {
            return ucfirst($part);
        }, explode('\\', $controller));

        return '\\'.str_replace('_', '\\', Module::IDENTIFIER).'\\Controller\\Adminhtml\\'.implode('\\', $controller);
    }

    private function getMethodComments(\ReflectionMethod $reflectionMethod)
    {
        $contentPhpFile = file_get_contents($reflectionMethod->getFileName());
        $contentPhpFile = explode(chr(10), $contentPhpFile);

        $commentsArray = [];
        for ($i=$reflectionMethod->getStartLine()-2; $i>0; $i--) {
            $contentPhpFile[$i] = trim($contentPhpFile[$i]);
            $commentsArray[] = $contentPhpFile[$i];
            if ($contentPhpFile[$i] == '/**' ||
                $contentPhpFile[$i] == '}') {
                break;
            }
        }

        $commentsArray = array_reverse($commentsArray);
        $commentsString = implode(chr(10), $commentsArray);

        return $commentsString;
    }

    //########################################
}
