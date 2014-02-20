<?php 


/**
 * Клас 'status_Retrieving'
 *
 * @category  vendors
 * @package   status
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class status_Retrieving extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Изтегляния';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'status_Wrapper';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('messageId', 'key(mvc=status_Messages)', 'caption=Съобщение');
        $this->FLD('userId', 'user', 'caption=Потребител,notNull');
        $this->FLD('sid', 'varchar(32)', 'caption=Идентификатор,notNull');
        $this->FLD('retTime', 'datetime', 'caption=Изтегляне');
        $this->FLD('hitTime', 'datetime', 'caption=Заявка');
        
        $this->setDbUnique('messageId, hitTime, sid, userId');
    }
    
    
    /**
     * Добавя запис за показване на съответното съобщение в даден таб
     * 
     * @param integer $messageId
     * @param datetime $hitTime
     * @param string $sid
     * @param integer $userId
     * 
     * @return integer - id на записа
     */
    static function addRetrieving($messageId, $hitTime, $sid=NULL, $userId=NULL)
    {
        // Записва 
        $rec = new stdClass();
        $rec->messageId = $messageId;
        $rec->hitTime = $hitTime;
        $rec->retTime = dt::now();
        
        // Ако има потребител
        if ($userId) {
            $rec->userId = $userId;
        }
        
        // Ако има индентификатор
        if ($sid) {
            $rec->sid = $sid;
        }
        
        $id = static::save($rec);
        
        return $id;
    }
    
    
    /**
     * Изтрива информацията за изтеглянията за съответното статус събщение
     * 
     * @param integer $messageId - id на съобщението
     */
    static function removeRetrieving($messageId)
    {
        $cnt = static::delete("#messageId = '{$messageId}'");
        
        return $cnt;
    }
    
    
    /**
     * Проверява дали съобщението и извлеченоо за даден потребител в съответния таб
     * 
     * @param integer $messageId
     * @param datetime $hitTime
     * @param string $sid
     * @param integer $userId
     * 
     * @return boolean
     */
    static function isRetrived($messageId, $hitTime, $sid=NULL, $userId=NULL)
    {
        // Вземаме всички съобщения, къ даден потребител със съответното време
        $query = static::getQuery();
        $query->where(array("#messageId = '[#1#]'", $messageId));
        $query->where(array("#hitTime = '[#1#]'", $hitTime));
        
        // Ако има идентификатор - когато не е логнат
        if ($sid) {
            $query->where(array("#sid = '[#1#]'", $sid));
        }
        
        // Ако има потребител - когато е логнат
        if ($userId) {
            $query->where(array("#userId = '[#1#]'", $userId));
        }
        
        // Ако има записи
        if ($query->count()) return TRUE;
    }
}
