<?php
// +----------------------------------------------------------------------+
// | PEAR :: HTML :: Progress                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Laurent Laville <pear@laurent-laville.org>                   |
// +----------------------------------------------------------------------+
//
// $Id: process.php,v 1.1 2004/08/23 14:19:25 tjdet Exp $

/**
 * The ActionProcess class provides final step of ProgressBar creation.
 * Manage php/css source-code save and cancel action.
 *
 * @version    1.2.0
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @access     public
 * @package    HTML_Progress
 * @subpackage Progress_UI
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 */

class ActionProcess extends HTML_QuickForm_Action
{
    function perform(&$page, $actionName)
    {
        if ($actionName == 'cancel') {
            echo '<h1>Progress Generator Task was canceled</h1>';
            echo '<p>None (PHP/CSS) source codes are available.</p>';
        } else {
            // Checks whether the pages of the controller are valid
            $page->isFormBuilt() or $page->buildForm();
            $page->controller->isValid();

            // what kind of source code is requested  
            $code = $page->exportValue('phpcss');
            $bar = $page->controller->createProgressBar();
            
            if (isset($code['C'])) {
                $this->exportOutput($bar->getStyle());
            }

            if (isset($code['P'])) {
                $structure = $bar->toArray();

                $lineEnd = OS_WINDOWS ? "\r\n" : "\n";
                
                $strPHP  = '<?php'.$lineEnd;
                $strPHP .= 'require_once \'HTML/Progress.php\';'.$lineEnd.$lineEnd;
                $strPHP .= '$progress = new HTML_Progress();'.$lineEnd;
                $strPHP .= '$progress->setIdent(\'PB1\');'.$lineEnd;
                    
                if ($bar->isIndeterminate()) {
                    $strPHP .= '$progress->setIndeterminate(true);'.$lineEnd;
                }
                if ($bar->isBorderPainted()) {
                    $strPHP .= '$progress->setBorderPainted(true);'.$lineEnd;
                }
                if ($bar->isStringPainted()) {
                    $strPHP .= '$progress->setStringPainted(true);'.$lineEnd;
                }
                if (is_null($structure['string'])) {
                    $strPHP .= '$progress->setString(null);';
                } else {
                    $strPHP .= '$progress->setString('.$structure['string'].');';
                }
                $strPHP .= $lineEnd;
                if ($structure['animspeed'] > 0) {
                    $strPHP .= '$progress->setAnimSpeed('.$structure['animspeed'].');'.$lineEnd;
                }
                if ($structure['dm']['minimum'] != 0) {
                    $strPHP .= '$progress->setMinimum('.$structure['dm']['minimum'].');'.$lineEnd;
                }
                if ($structure['dm']['maximum'] != 100) {
                    $strPHP .= '$progress->setMaximum('.$structure['dm']['maximum'].');'.$lineEnd;
                }
                if ($structure['dm']['increment'] != 1) {
                    $strPHP .= '$progress->setIncrement('.$structure['dm']['increment'].');'.$lineEnd;
                }
                $strPHP .= $lineEnd;
                $strPHP .= '$ui =& $progress->getUI();'.$lineEnd;

                $orient = ($structure['ui']['orientation'] == '1') ? 'HTML_PROGRESS_BAR_HORIZONTAL' : 'HTML_PROGRESS_BAR_VERTICAL';
                $strPHP .= '$ui->setOrientation('.$orient.');'.$lineEnd;
                $strPHP .= '$ui->setFillWay(\''.$structure['ui']['fillway'].'\');'.$lineEnd;

            /* Page 1: Progress attributes **************************************************/
                $strPHP .= $this->_attributesArray('$ui->setProgressAttributes(', $structure['ui']['progress']);
                $strPHP .= $lineEnd;

            /* Page 2: Cell attributes ******************************************************/
                $strPHP .= '$ui->setCellCount('.$structure['ui']['cell']['count'].');'.$lineEnd;
                unset($structure['ui']['cell']['count']);  // to avoid dupplicate entry in attributes
                $strPHP .= $this->_attributesArray('$ui->setCellAttributes(', $structure['ui']['cell']);
                $strPHP .= $lineEnd;

            /* Page 3: Border attributes ****************************************************/
                $strPHP .= $this->_attributesArray('$ui->setBorderAttributes(', $structure['ui']['border']);
                $strPHP .= $lineEnd;

            /* Page 4: String attributes ****************************************************/
                $strPHP .= $this->_attributesArray('$ui->setStringAttributes(', $structure['ui']['string']);
                $strPHP .= $lineEnd.$lineEnd;

                $strPHP .= '// code below is only for run demo; its not ncecessary to create progress bar'.$lineEnd;
                $strPHP .= 'echo \'<style type="text/css">\'.$progress->getStyle().\'</style>\';'.$lineEnd;
                $strPHP .= 'echo \'<script type="text/javascript">\'.$progress->getScript().\'</script>\';'.$lineEnd;
                $strPHP .= 'echo $progress->toHtml();'.$lineEnd;
                $strPHP .= '$progress->run();'.$lineEnd;
                $strPHP .= '?>';
                $this->exportOutput($strPHP);
            }

            // reset session data
            $page->controller->container(true);
        }
    }

    function exportOutput($str, $mime = 'text/plain', $charset = 'iso-8859-1')
    {
        if (!headers_sent()) {
            header("Expires: Tue, 1 Jan 1980 12:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-cache");
            header("Pragma: no-cache");
            header("Content-Type: $mime; charset=$charset");
        }
        print $str;
    }

    function _attributesArray($str, $attributes)
    {
        $strPHP = $str . 'array(';
        foreach ($attributes as $attr => $val) {
            if (is_integer($val)) {
                $strPHP .= "'$attr'=>$val, ";
            } elseif (is_bool($val)) {
                $strPHP .= "'$attr'=>".($val?'true':'false').', ';
            } else {
                $strPHP .= "'$attr'=>'$val', ";
            }   
        }
        $strPHP = ereg_replace(', $', '', $strPHP);
        $strPHP .= '));';
        return $strPHP;
    }
}
?>