<?php



/**
 * Клас 'core_Detail' - Мениджър за детайлите на бизнес обектите
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Detail extends core_Manager
{
    
    
    /**
     * Полето-ключ към мастъра
     */
    var $masterKey;
    
    
    /**
     * По колко реда от резултата да показва на страница в детайла на документа
     * Стойност '0' означава, че детайла няма да се странира
     */
    var $listItemsPerPage = 0;
    
    
    /**
     * Изпълнява се след началното установяване на модела
     */
    static function on_AfterDescription(&$mvc)
    {
        expect($mvc->masterKey);
        
        $mvc->fields[$mvc->masterKey]->silent = silent;
        
        if(!isset($mvc->fields[$mvc->masterKey]->input)) {
            $mvc->fields[$mvc->masterKey]->input = hidden;
        }
        
        setIfNot($mvc->fetchFieldsBeforeDelete, $mvc->masterKey);
        
        $mvc->setupMaster(NULL);
    }
    
    
    /**
     * Подготвяме  общия изглед за 'List'
     */
    function prepareDetail_($data)
    {
        // Очакваме да masterKey да е зададен
        expect($this->masterKey);
        
        // Подготвяме заявката за детайла
        $this->prepareDetailQuery($data);
        
        // Подготвяме полетата за показване
        $this->prepareListFields($data);
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);
        
        // Подготвяме лентата с инструменти
        $this->prepareListToolbar($data);
        
        // Подготвяме редовете от таблицата
        $this->prepareListRecs($data);
        
        // Подготвяме вербалните стойности за редовете
        $this->prepareListRows($data);
        
        return $data;
    }
    
    
    /**
     * Създаване на шаблона за общия List-изглед
     */
    function renderDetailLayout_($data)
    {
        
        $className = cls::getClassName($this);
        
        // Шаблон за листовия изглед
        $listLayout = new ET("
            <div class='clearfix21 {$className}'>
                [#ListPagerTop#]
                [#ListTable#]
                [#ListSummary#]
                [#ListToolbar#]
            </div>
        ");
        
        return $listLayout;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    function renderDetail_($data)
    {
        if (!isset($this->currentTab)) {
            $this->currentTab = $this->Master->title;
        }
        
        // Рендираме общия лейаут
        $tpl = $this->renderDetailLayout($data);
        
        // Попълваме обобщената информация
        $tpl->append($this->renderListSummary($data), 'ListSummary');
        
        // Попълваме таблицата с редовете
        $tpl->append($this->renderListTable($data), 'ListTable');
        
        // Попълваме таблицата с редовете
        $tpl->append($this->renderListPager($data), 'ListPagerTop');
        
        // Попълваме долния тулбар
        $tpl->append($this->renderListToolbar($data), 'ListToolbar');
        
        return $tpl;
    }
    
    
    /**
     * Подготвя заявката за данните на детайла
     */
    function prepareDetailQuery_($data)
    {
        $this->Master = $data->masterMvc;
        
        // Създаваме заявката
        $data->query = $this->getQuery();
        
        // Добавяме връзката с мастер-обекта
        $data->query->where("#{$this->masterKey} = {$data->masterId}");
        
        return $data;
    }
    
    
    /**
     * Подготвя лентата с инструменти за табличния изглед
     */
    function prepareListToolbar_(&$data)
    {
        $data->toolbar = cls::get('core_Toolbar');
 
        $masterKey = $this->masterKey;
        
        if($data->masterId) {
            $rec = new stdClass();
            $rec->{$masterKey} = $data->masterId;
        }

        if ($this->haveRightFor('add', $rec)) {
            $data->toolbar->addBtn('Нов запис', array(
                    $this,
                    'add',
                    $this->masterKey => $data->masterId,
                    'ret_url' => array($this->Master, 'single', $rec->{$masterKey})
                ),
                'id=btnAdd,class=btn-add');
        }
        
        return $data;
    }
    
    
    /**
     * Позволява задаване на Master-мениджър за всеки конкретен запис-детайл.
     * 
     * @param stdClass $rec
     */
    public function setupMaster($rec)
    {
        if (!$this->Master) {
            if ($this->masterClass = $this->fields[$this->masterKey]->type->params['mvc']) {
                $this->Master = cls::get($this->masterClass);
            }
        }
        
        expect($this->Master instanceof core_Master);
    }
    
    
    /**
     * Подготвя формата за редактиране
     */
    function prepareEditForm_($data)
    {
        parent::prepareEditForm_($data);
        
        $this->setupMaster($data);
        
        $masterKey = $this->masterKey;
        
        expect($data->masterId = $data->form->rec->{$masterKey}, $data->form->rec);
        
        expect($data->masterRec = $this->Master->fetch($data->masterId));
        
        $title = $this->Master->getTitleById($data->masterId);
        
        if($this->singleTitle) {
            $single = ' на| ' . mb_strtolower($this->singleTitle) . '|';
        }

        $data->form->title = $data->form->rec->id ? "Редактиране{$single} в" : "Добавяне{$single} към";
        
        $data->form->title .= "|* \"" . str::limitLen($title, 32) . "\"";
        
        return $data;
    }
    
    
    /**
     * Връща ролите, които могат да изпълняват посоченото действие
     */
    function getRequiredRoles_(&$action, $rec = NULL, $userId = NULL)
    {
        
        if($action == 'read') {
            // return 'no_one';
        }
        
        if($action == 'write' && isset($rec)) {
            
            expect($masterKey = $this->masterKey);
            expect($this->Master instanceof core_Master, $this);
            
            if($rec->{$masterKey}) {
                $masterRec = $this->Master->fetch($rec->{$masterKey});
            }
            
            if ($masterRec) {
                return $this->Master->getRequiredRoles('edit', $masterRec, $userId);
            }
        }

 
        
        return parent::getRequiredRoles_($action, $rec, $userId);
    }
    
    
    /**
     * След запис в детайла извиква събитието 'AfterUpdateDetail' в мастъра
     */
    static function on_AfterSave($mvc, &$id, $rec, $fieldsList = NULL)
    {
        $masterKey = $mvc->masterKey;
        
        if($rec->{$masterKey}) {
            $masterId = $rec->{$masterKey};
        } elseif($rec->id) {
            $masterId = $mvc->fetchField($rec->id, $masterKey);
        }
        
        $mvc->Master->invoke('AfterUpdateDetail', array($masterId, $mvc));
    }
    
    
    /**
     * След изтриване в детайла извиква събитието 'AfterUpdateDetail' в мастъра
     */
    static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        
        if($numRows) {
            $masterKey = $mvc->masterKey;
            
            foreach($query->getDeletedRecs() as $rec) {
                $masterId = $rec->{$masterKey};
                $mvc->Master->invoke('AfterUpdateDetail', array($masterId, $mvc));
            }
        }
    }


    /**
     * Връща URL към единичния изглед на мастера
     */
    function getSingleUrl($id)
    {
        $mRec = self::fetch($id);
        $masterField = $this->masterKey;
        $url = array($this->Master, 'single', $mRec->{$masterField});

        return $url;
    }

}
