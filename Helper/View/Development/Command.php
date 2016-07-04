<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View\Development;

//TODO
class Command extends \Ess\M2ePro\Helper\AbstractHelper
{
    //########################################

    const CONTROLLER_MODULE_MODULE          = 'adminhtml_development_module_module';
    const CONTROLLER_MODULE_SYNCHRONIZATION = 'adminhtml_development_module_synchronization';
    const CONTROLLER_MODULE_INTEGRATION     = 'adminhtml_development_module_integration';

    const CONTROLLER_TOOLS_M2EPRO_GENERAL   = 'adminhtml_development_tools_m2ePro_general';
    const CONTROLLER_TOOLS_M2EPRO_INSTALL   = 'adminhtml_development_tools_m2ePro_install';
    const CONTROLLER_TOOLS_MAGENTO          = 'adminhtml_development_tools_magento';
    const CONTROLLER_TOOLS_ADDITIONAL       = 'adminhtml_development_tools_additional';

    const CONTROLLER_DEBUG                  = 'adminhtml_development';

    const CONTROLLER_BUILD                  = 'adminhtml_development_build';

    //########################################

    public function parseGeneralCommandsData($controller)
    {
        $tempClass = $this->getHelper('View\Development\Controller')->loadControllerAndGetClassName($controller);

        $reflectionClass = new \ReflectionClass ($tempClass);
        $reflectionMethods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Get actions methods
        // ---------------------------------------
        $actions = array();
        foreach ($reflectionMethods as $reflectionMethod) {

            $className = $reflectionClass->getMethod($reflectionMethod->name)
                                         ->getDeclaringClass()->name;
            $methodName = $reflectionMethod->name;

            if (substr($className,0,10) != 'Ess_M2ePro') {
                continue;
            }
            if ($methodName == 'indexAction') {
                continue;
            }
            if (substr($methodName,strlen($methodName)-6) != 'Action') {
                continue;
            }

            $methodName = substr($methodName,0,strlen($methodName)-6);

            $actions[] = $methodName;
        }
        // ---------------------------------------

        // Print method actions
        // ---------------------------------------
        $methods = array();
        foreach ($actions as $action) {

            $controllerName = $this->getHelper('View\Development\Controller')->getControllerClassName($controller);
            $reflectionMethod = new \ReflectionMethod ($controllerName,$action.'Action');

            $commentsString = $this->getMethodComments($reflectionMethod);

            preg_match('/@hidden/', $commentsString, $matches);
            if (isset($matches[0])) {
                continue;
            }

            $methodInvisible = false;
            preg_match('/@invisible/', $commentsString, $matches);
            isset($matches[0]) && $methodInvisible = true;

            $methodNonProduction = false;
            preg_match('/@non-production/', $commentsString, $matches);
            isset($matches[0]) && $methodNonProduction = true;

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

            $methods[] = array(
                'invisible'      => $methodInvisible,
                'non_production' => $methodNonProduction,
                'title'          => $methodTitle,
                'description'    => $methodDescription,
//                'url'            => Mage::helper('adminhtml')->getUrl('*/' . $controller . '/' . $action), todo url
                'content'        => $methodContent,
                'new_line'       => $methodNewLine,
                'confirm'        => $methodConfirm,
                'prompt'      => array(
                    'text' => $methodPrompt,
                    'var'  => $methodPromptVar
                ),
                'components'  => $methodComponents,
                'new_window'  => $methodNewWindow
            );
        }
        // ---------------------------------------

        return $methods;
    }

    // ---------------------------------------

    public function parseDebugCommandsData($controller)
    {
        $tempClass = $this->getHelper('View\Development\Controller')->loadControllerAndGetClassName($controller);

        $reflectionClass = new \ReflectionClass ($tempClass);
        $reflectionMethods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Get actions methods
        // ---------------------------------------
        $actions = array();
        foreach ($reflectionMethods as $reflectionMethod) {

            $className = $reflectionClass->getMethod($reflectionMethod->name)->getDeclaringClass()->name;
            $methodName = $reflectionMethod->name;

            if (substr($className,0,10) != 'Ess_M2ePro') {
                continue;
            }
            if ($methodName == 'indexAction') {
                continue;
            }
            if (substr($methodName,strlen($methodName)-6) != 'Action') {
                continue;
            }

            $methodName = substr($methodName,0,strlen($methodName)-6);

            $actions[] = $methodName;
        }
        // ---------------------------------------

        // Print method actions
        // ---------------------------------------
        $methods = array();
        foreach ($actions as $action) {

            $controllerName = $this->getHelper('View\Development\Controller')->getControllerClassName($controller);
            $reflectionMethod = new \ReflectionMethod ($controllerName,$action.'Action');

            $commentsString = $this->getMethodComments($reflectionMethod);

            preg_match('/@hidden/', $commentsString, $matchesHidden);

            if (isset($matchesHidden[0])) {
                continue;
            }

            preg_match('/@title[\s]*\"(.*)\"/', $commentsString, $matchesTitle);
            preg_match('/@description[\s]*\"(.*)\"/', $commentsString, $matchesDescription);

            if (!isset($matchesTitle[1]) || !isset($matchesDescription[1])) {
                continue;
            }

            $methodTitle = $matchesTitle[1];
            $methodDescription = $matchesDescription[1];

//            $methodUrl = Mage::helper('adminhtml')->getUrl('*/'.$controller.'/'.$action); todo url

            preg_match('/@confirm[\s]*\"(.*)\"/', $commentsString, $matchesConfirm);
            $methodConfirm = '';
            if (isset($matchesConfirm[1])) {
                $methodConfirm = $matchesConfirm[1];
            }

            preg_match('/new_window/', $commentsString, $matchesNewWindow);
            $methodNewWindow = isset($matchesNewWindow[0]);

            $methods[] = array(
                'title' => $methodTitle,
                'description' => $methodDescription,
//                'url' => $methodUrl, todo uncoment
                'confirm' => $methodConfirm,
                'new_window' => $methodNewWindow
            );
        }
        // ---------------------------------------

        return $methods;
    }

    //########################################

    private function getMethodComments(\ReflectionMethod $reflectionMethod)
    {
        $contentPhpFile = file_get_contents($reflectionMethod->getFileName());
        $contentPhpFile = explode(chr(10),$contentPhpFile);

        $commentsArray = array();
        for ($i=$reflectionMethod->getStartLine()-2;$i>0;$i--) {
            $contentPhpFile[$i] = trim($contentPhpFile[$i]);
            $commentsArray[] = $contentPhpFile[$i];
            if ($contentPhpFile[$i] == '/**' ||
                $contentPhpFile[$i] == '}') {
                break;
            }
        }

        $commentsArray = array_reverse($commentsArray);
        $commentsString = implode(chr(10),$commentsArray);

        return $commentsString;
    }

    //########################################
}