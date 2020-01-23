<?php


/**
 * Експортиране на фирми->Група
 */
defIfNot('SYNC_COMPANY_GROUP', '');

/**
 * Име на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('SYNC_ESHOP_GRPUPS', '');


/**
 * Държавата на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('SYNC_EXPORT_URL', '');

 



/**
 * Клас 'sync_Setup'  
 *
 *
 * @category  bgerp
 * @package   sync
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sync_Setup extends core_ProtoSetup
{
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'sync_Map';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
        
    /**
     * Описание на модула
     */
    public $info = 'Синхронизиране на данните между две bgERP системи';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'SYNC_COMPANY_GROUP' => array('key(mvc=crm_Groups, allowEmpty)', 'caption=Експортиране на фирми->Група'),
        'SYNC_ESHOP_GRPUPS' => array('keylist(mvc=eshop_Groups, select=name, allowEmpty)', 'caption=Експортиране на е-магазин->Групи'),
        'SYNC_EXPORT_URL' => array('url', 'caption=Импортиране->URL'),
    );
    
   
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'sync_Map',
    );
    
  
}
