<?php

/**
 * Урл за изпращане на СМС-и през Мобио
 */
defIfNot('MOBIO_URL', '');


/**
 * Дали поддържа UTF-8
 */
defIfNot('MOBIO_SUPPORT_UTF8', FALSE);


/**
 * Максималната дължина на стринга
 */
defIfNot('MOBIO_MAX_STRING_LEN', 160);


/**
 * Стринг с позволените имена за изпращач
 */
defIfNot('MOBIO_ALLOWED_USER_NAMES', '');


/**
 * class mobio_Setup
 *
 * Инсталиране/Деинсталиране на плъгина за изпращане на SMS-и чрез mobio
 *
 *
 * @category  vendors
 * @package   mobio
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mobio_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "SMS изпращане чрез Mobio";
    

    var $configDescription = array (
        'MOBIO_URL' => array('url', 'mandatory'),
    
        'MOBIO_SUPPORT_UTF8' => array('enum(no=Не, yes=Да)'),
        'MOBIO_MAX_STRING_LEN' => array('int'),
        'MOBIO_ALLOWED_USER_NAMES' => array('varchar'),
        );
    
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'mobio_SMS',
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