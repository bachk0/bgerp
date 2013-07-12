<?php

/**
 * Константи за изпращане на СМС-и през Pro-SMS
 */

/**
 * @todo Чака за документация...
 */
defIfNot('PROSMS_URL', '');

/**
 * @todo Чака за документация...
 */
defIfNot('PROSMS_USER', '');


/**
 * @todo Чака за документация...
 */
defIfNot('PROSMS_PASS', '');


/**
 * class prosms_Setup
 *
 * Инсталиране/Деинсталиране на плъгина за изпращане на SMS-и чрез prosms
 *
 *
 * @category  vendors
 * @package   prosms
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class prosms_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "SMS изпращане чрез prosms";
    

    var $configDescription = array (
        'PROSMS_URL' => array('url', 'mandatory'),
        'PROSMS_USER' => array('identifier', 'mandatory'),
        'PROSMS_PASS' => array('password', 'mandatory'),
        );
    
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'prosms_SMS',
        );

    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}