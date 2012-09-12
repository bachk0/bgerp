<?php



/**
 * Клас  'type_Email' - Тип за имейл
 *
 * Има валидираща функция
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Email extends type_Varchar {
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    var $dbFieldLen = 80;
    
    
    /**
     * Инициализиране на типа
     * Задава, че в базата имейлите ще са case-insensitive
     */
    function init($params = array())
    {
        setIfNot($params['params']['ci'], 'ci');
        
        parent::init($params);
    }
    
    
    /**
     * Превръща вербална стойност с имейл към вътрешно представяне
     */
    function fromVerbal($value)
    {
        $value = trim($value);
        
        $value = static::replaceEscaped($value);

        if(empty($value)) return NULL;
                
        if(!$this->isValidEmail($value)) {
            $this->error = 'Некоректен имейл';
            
            return FALSE;
        } else {
            
            return $value;
        }
    }



    /**
     * Замества низове, които се използват за скриване на ймейл адресите от ботовете
     */
    static function replaceEscaped($value)
    {
        $from = array('<at>', '[at]', '(at)', '{at}', ' at ', ' <at> ',
            ' [at] ', ' (at) ', ' {at} ');
        $to = array('@', '@', '@', '@', '@', '@', '@', '@', '@');
        
        $value = str_ireplace($from, $to, $value);
        
        $from = array('<dot>', '[dot]', '(dot)', '{dot}', ' dot ',
            ' <dot> ', ' [dot] ', ' (dot) ', ' {dot} ');
        $to = array('.', '.', '.', '.', '.', '.', '.', '.', '.');
        
        $value = str_ireplace($from, $to, $value);

        return $value;
    }
    
    
    /**
     * Добавя атрибут за тип = email, ако изгледа е мобилен
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        if(Mode::is('screenMode', 'narrow') && empty($attr['type'])) {
            $attr['type'] = 'email';
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Проверява дали е валиден имейл
     */
    static function isValidEmail($email)
    {
        if (!$email) return NULL;
        
        if (preg_match("/[\\000-\\037]/", $email)) {
            
            return FALSE;
        }
        
        $pattern = "/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])" .
        "[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD";
        
        if(!preg_match($pattern, $email)){
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Преобразува имейл-а в човешки вид
     */
    function toVerbal($email)
    {
        if(empty($email)) return NULL;
        
        if(!haveRole('user')) {
            $verbal = str_replace('@', " [аt] ", $email);
        } else {
            $verbal =  $email;
        }
        
        if($this->params['link'] != 'no') {
            $verbal = $this->addHyperlink($email, $verbal);
        } 
        

        return $verbal;
    }
    
    
    /**
     * Превръща имейлите в препратка за изпращане на имейл
     */
    function addHyperlink_($email, $verbal)
    {
        if(Mode::is('text', 'html') || !Mode::is('text')) {
            list($user, $domain) = explode('@', $email);
            $domain = '&#64;' . $domain;
            $value = "<script>document.write(\"<a href='mailto:{$user}\" + \"{$domain}'><span style='display:none;'>\");</script> {$verbal}<script>document.write(\"</span>\" + \"{$user}\" + \"{$domain}</a>\");</script>";
        }
        
        return $value;
    }
    
    
    /**
     * Извлича домейна (частта след `@`) от имейл адрес
     *
     * @param string $value имейл адрес
     * @return string
     */
    static function domain($value)
    {
        list(, $domain) = explode('@', $value, 2);
        
        $domain = empty($domain) ? FALSE : trim($domain);
        
        return $domain;
    }
}