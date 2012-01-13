<?php



/**
 * Клас 'bank_AccountTypes' -
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class bank_AccountTypes extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper';
    
    
    
    /**
     * Заглавие
     */
    var $title = 'Типове банкови сметки';
    
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Тип');
        $this->FLD('note', 'text', 'caption=Забележка');
    }
    
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        $data = array(
            array(
                'name' => 'Разплащателна',
                'note' => ''
            ),
            array(
                'name' => 'Депозитна',
                'note' => 'за съхранение на пари'
            ),
            array(
                'name' => 'Бюджетна',
                'note' => 'за съхранение на пари на разпоредителите с бюджетни средства и пари, отпуснати от бюджета на други лица'
            ),
            array(
                'name' => 'Спестовна',
                'note' => 'за съхранение на пари на граждани срещу издаване на лична спестовна книжка'
            ),
            array(
                'name' => 'Набирателна',
                'note' => 'за съхранение на пари, предоставени за разпореждане от клиента на негово поделение'
            ),
            array(
                'name' => 'Акредитивна',
                'note' => 'за предоставени за разплащане на клиента с трето лице, което има право да получи средствата при изпълнение на условията, поставени при откриване на акредитива'
            ),
            array(
                'name' => 'Ликвидационна',
                'note' => 'за съхранение на пари на лица, обявени в ликвидация'
            ),
            array(
                'name' => 'Особенa',
                'note' => 'за съхранение на пари на лица, за които е открито производство по несъстоятелност'
            ),
        );
        
        $nAffected = 0;
        
        foreach ($data as $rec) {
            $rec = (object)$rec;
            
            if (!$this->fetch("#name='{$rec->name}'")) {
                if ($this->save($rec)) {
                    $nAffected++;
                }
            }
        }
        
        if ($nAffected) {
            $res .= "<li>Добавени са {$nAffected} тип(а) банкови сметки.</li>";
        }
    }
}