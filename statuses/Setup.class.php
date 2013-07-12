<?php

/**
 * Версията на toast message
 */
defIfNot('STATUSES_TOAST_MESSAGE_VERSION', '0.3.0f');


/**
 * class toast_Setup
 *
 * Инсталиране/Деинсталиране на плъгина за показване на статъс съобщения
 *
 * @category  vendors
 * @package   statuses
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class statuses_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Статус съобщения";
    

    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'STATUSES_TOAST_MESSAGE_VERSION' => array('text'),                
    );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за показване на статусите като toast съобщения
        $html .= $Plugins->installPlugin('Toast messages', 'statuses_Toast', 'core_Statuses', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	$html = parent::deinstall();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Деинсталираме toast съобщения
        if($delCnt = $Plugins->deinstallPlugin('statuses_Toast')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'core_Statuses'";
        } else {
            $html .= "<li>Не са премахнати закачания на плъгина";
        }
        return $res;
    }
}