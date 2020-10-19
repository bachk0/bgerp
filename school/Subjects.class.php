<?php


/**
 * class school_Subjects
 *
 * Обучителни дисциплини
 *
 *
 * @category  bgerp
 * @package   edu
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class school_Subjects extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Програми за обучителни дисциплини';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Предмет';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, school_Wrapper, plg_Sorting, plg_Printing, plg_State2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,name,description,teachers';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, edu';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, edu';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, edu';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, edu';
    
    
    /**
     * Шаблон за единичния изглед
     */
    // public $singleLayoutFile = '';
    

    /**
     * Детайли за зареждане
     */
    public $details = 'school_SubjectDetails';


    /**
     * Хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'name';
    

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('code', 'varchar(16)', 'caption=Код,mandatory,smartCenter');
        $this->FLD('name', 'varchar(128)', 'caption=Наименование,mandatory');
        $this->FLD('credits', 'int', 'caption=ECTS кредити,smartCenter');
        $this->FLD('description', 'richtext', 'caption=@Описание');
        $this->FLD('teachers', 'userlist(roles=teacher)', 'caption=Преподаватели');
        $this->FLD('hoursL', 'int', 'caption=Хорариум->Лекции,unit=часа');
        $this->FLD('hoursE', 'int', 'caption=Хорариум->Упражнения,unit=часа');
        $this->FLD('hoursS', 'int', 'caption=Хорариум->Семинари,unit=часа');
        $this->FLD('hoursW', 'int', 'caption=Хорариум->Практика,unit=часа');
        $this->FLD('hoursR', 'int', 'caption=Хорариум->Самоподготовка,unit=часа');

    }
    
}
