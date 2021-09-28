<?php


/**
 * Клас 'trans_Lines' - Документ за Транспортни линии
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_Lines extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Транспортни линии';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Tl';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, trans_Wrapper, plg_Printing, plg_Clone, doc_DocumentPlg, bgerp_plg_Blank, plg_Search, change_Plugin, doc_ActivatePlg, doc_plg_SelectFolder, doc_plg_Close, acc_plg_DocumentSummary';
    
    
    /**
     * Кой може да променя активирани записи
     */
    public $canChangerec = 'ceo, trans';
    
    
    /**
     * По кои полета ще се търси
     */
    public $searchFields = 'title, vehicle, forwarderId, forwarderPersonId';
    
    
    /**
     * Поле за единичен изглед
     */
    public $rowToolsSingleField = 'handler';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, trans';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, trans';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, trans';
    
    
    /**
     * Кой има право да прави документа на заявка?
     */
    public $canPending = 'ceo, trans';
    
    
    /**
     * Кой има право да пише?
     */
    public $canWrite = 'ceo, trans';
    
    
    /**
     * Кой може да пише?
     */
    public $canClose = 'ceo,trans,store';
    
    
    /**
     * Кой може да активира?
     */
    public $canActivate = 'ceo,trans,store';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'trans_LineDetails';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'start, handler=Документ, folderId, state,createdOn, createdBy';
    
    
    /**
     * Кои полета да могат да се променят след активацията на документа
     */
    public $changableFields = 'title, start, repeat, vehicle, forwarderId, forwarderPersonId';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Транспортна линия';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'trans/tpl/SingleLayoutLines.shtml';
    
    
    /**
     * Файл за единичния изглед в мобилен
     */
    public $singleLayoutFileNarrow = 'trans/tpl/SingleLayoutLinesNarrow.shtml';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/door_in.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.5|Логистика';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'title,start,repeat,countTotal,countReady';
    
    
    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = true;
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'start,createdOn';
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForTeam = 'ceo,trans,store';
    
    
    /**
     * Кои роли могат да филтрират потребителите по екип в листовия изглед
     */
    public $filterRolesForAll = 'ceo,trans,store';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar', 'caption=Заглавие,mandatory');
        $this->FLD('start', 'datetime', 'caption=Начало, mandatory');
        $this->FLD('repeat', 'time(suggestions=1 ден|1 седмица|1 месец|2 дена|2 седмици|2 месеца|3 седмици)', 'caption=Повторение');
        $this->FLD('state', 'enum(draft=Чернова,,pending=Заявка,active=Активен,rejected=Оттеглен,closed=Затворен)', 'caption=Състояние,input=none');
        $this->FLD('forwarderId', 'key2(mvc=crm_Companies,select=name,allowEmpty)', 'caption=Превозвач->Транспортна фирма');
        $this->FLD('vehicle', 'varchar', 'caption=Превозвач->Превозно средство,oldFieldName=vehicleId');
        $this->FLD('forwarderPersonId', 'key2(mvc=crm_Persons,select=name,group=employees,allowEmpty)', 'caption=Превозвач->МОЛ');
        $this->FLD('caseId', 'key(mvc=cash_Cases,select=name)', 'caption=Превозвач->Инкасиране в');
        $this->FLD('description', 'richtext(bucket=Notes,rows=4)', 'caption=Допълнително->Бележки');
        
        $this->FLD('countReady', 'int', 'input=none,notNull,value=0');
        $this->FLD('countTotal', 'int', 'input=none,notNull,value=0');
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $titleArr = explode('/', $rec->title);
        $start = dt::mysql2verbal($rec->start, 'd.m.Y H:i');
        $start = str_replace(' 00:00', '', $start);
        
        $title = (countR($titleArr) == 2) ? $titleArr[1] : $rec->title;
        $title = str::limitLen($title, 32);
        $recTitle = "{$start}/{$title} ({$rec->countReady}/{$rec->countTotal})";
        
        return $recTitle;
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->setFieldTypeParams('folder', array('containingDocumentIds' => trans_Lines::getClassId()));
        $data->listFilter->FLD('lineState', 'enum(all=Всички,draft=Чернова,pending=Заявка,active=Активен,closed=Затворен)', 'caption=Състояние');
        $data->listFilter->showFields .= ',lineState,search';
        $data->listFilter->input();
        
        $data->query->orderBy('#state');
        $data->query->orderBy('#start', 'DESC');
        
        if($filterRec = $data->listFilter->rec){
            if(isset($filterRec->lineState) && $filterRec->lineState != 'all'){
                $data->query->where("#state = '{$filterRec->lineState}'");
            }

            if(isset($filterRec->folder)){
                unset($data->listFields['folderId']);
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if ($data->toolbar->haveButton('btnClose')) {
            if (self::countDocumentsByState($rec->id, 'draft')) {
                $data->toolbar->setError('btnClose', 'Линията не може да бъде затворена докато има чернови документи към нея|*!');
            }
        }
        
        if ($mvc->haveRightFor('single', $data->rec) && $rec->state != 'rejected') {
            $url = array($mvc, 'single', $rec->id, 'Printing' => 'yes', 'Width' => 'yes');
            $data->toolbar->addBtn('Печат (Детайли)', $url, 'target=_blank,row=2', 'ef_icon = img/16/printer.png,title=Разширен печат на документа');
        }
        
        if (!$data->toolbar->haveButton('btnActivate')) {
            if (self::countDocumentsByState($rec->id, 'pending,draft,rejected')) {
                $data->toolbar->addBtn('Активиране', array(), false, array('error' => 'В транспортната линия има документи, които не са контирани|*!', 'ef_icon' => 'img/16/lightning.png', 'title' => 'Активиране на документа'));
            }
        }
    }
    
    
    /**
     * След подготовка на формата
     */
    protected static function on_AfterPrepareEditForm(core_Mvc $mvc, $data)
    {
        $form = &$data->form;
        
        $vehicleOptions = trans_Vehicles::makeArray4Select();
        if (countR($vehicleOptions) && is_array($vehicleOptions)) {
            $form->setSuggestions('vehicle', array('' => '') + arr::make($vehicleOptions, true));
        }
        
        $form->setOptions('forwarderPersonId', trans_Vehicles::getDriverOptions());
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;

            if ($rec->start < dt::today()) {
                $form->setError('start', 'Не може да се създаде линия за предишен ден!');
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-single'])) {
            if (!empty($rec->vehicle)) {
                if ($vehicleRec = trans_Vehicles::fetch(array("#name = '[#1#]'", $rec->vehicle))) {
                    $row->vehicle = trans_Vehicles::getHyperlink($vehicleRec->id, true);
                    $row->regNumber = trans_Vehicles::getVerbal($vehicleRec, 'number');
                }
            }
            
            $ownCompanyData = crm_Companies::fetchOwnCompany();
            $row->myCompany = ht::createLink($ownCompanyData->company, crm_Companies::getSingleUrlArray($ownCompanyData->companyId));

            $createdByUserLink = crm_Profiles::createLink($rec->createdBy);
            $row->logistic = core_Users::getVerbal($rec->createdBy, 'names');
            $row->logistic .= " ({$createdByUserLink})";

            if (isset($rec->forwarderPersonId) && !Mode::isReadOnly()) {
                $row->forwarderPersonId = ht::createLink($row->forwarderPersonId, crm_Persons::getSingleUrlArray($rec->forwarderPersonId));
            }
            
            if (isset($rec->forwarderId)) {
                $row->forwarderId = ht::createLink(crm_Companies::getVerbal($rec->forwarderId, 'name'), crm_Companies::getSingleUrlArray($rec->forwarderId));
            }
            
            if(isset($rec->caseId)){
                $row->caseId = cash_Cases::getHyperlink($rec->caseId, true);
            }
        }
        
        $row->handler = $mvc->getHyperlink($rec->id, true);
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow_($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);
        
        $row = (object) array(
            'title' => $title,
            'authorId' => $rec->createdBy,
            'author' => $this->getVerbal($rec, 'createdBy'),
            'state' => $rec->state,
            'recTitle' => $title,
        );
        
        return $row;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $amount = $amountReturned = $weight = $volume = 0;
        $sumWeight = $sumVolume = true;
        $transUnits = $calcedUnits = array();
        
        $dQuery = trans_LineDetails::getQuery();
        $dQuery->where("#lineId = {$data->rec->id} AND #containerState != 'rejected' AND #status != 'removed'");

        while ($dRec = $dQuery->fetch()) {
            $Document = doc_Containers::getDocument($dRec->containerId);
            $transInfo = $Document->getTransportLineInfo($data->rec->id);
            $isStoreDocument = $Document->haveInterface('store_iface_DocumentIntf');
            
            if(!$isStoreDocument && $dRec->containerState == 'active'){
                if($transInfo['baseAmount'] < 0){
                    $amountReturned += $transInfo['baseAmount'];
                } else {
                    $amount += $transInfo['baseAmount'];
                }
            }
            
            // Сумиране на ЛЕ от документа и подготвените
            trans_Helper::sumTransUnits($transUnits, $dRec->readyLu);
            trans_Helper::sumTransUnits($calcedUnits, $dRec->documentLu);
            
            // Сумиране на теглото от редовете
            if ($sumWeight === true) {
                if ($transInfo['weight']) {
                    $weight += $transInfo['weight'];
                } elseif($isStoreDocument) {
                    unset($weight);
                    $sumWeight = false;
                }
            }
            
            // Сумиране на обема от редовете
            if ($sumVolume === true) {
                if ($transInfo['volume']) {
                    $volume += $transInfo['volume'];
                } elseif($isStoreDocument) {
                    unset($volume);
                    $sumVolume = false;
                }
            }
        }
        
        // Оцветяване на ЛЕ
        $logisticUnitsSum = trans_LineDetails::colorTransUnits($calcedUnits, $transUnits);
        $calcedUnits = empty($logisticUnitsSum->documentLu) ? "<span class='quiet'>N/A</span>" : $logisticUnitsSum->documentLu;
        $transUnits = empty($logisticUnitsSum->readyLu) ? "<span class='quiet'>N/A</span>" : $logisticUnitsSum->readyLu;
        
        // Показване на сумарната информация
        $data->row->logisticUnitsDocument = core_Type::getByName('html')->toVerbal($calcedUnits);
        $data->row->logisticUnits = core_Type::getByName('html')->toVerbal($transUnits);
        $data->row->weight = (!empty($weight)) ? cls::get('cat_type_Weight')->toVerbal($weight) : "<span class='quiet'>N/A</span>";
        $data->row->volume = (!empty($volume)) ? cls::get('cat_type_Volume')->toVerbal($volume) : "<span class='quiet'>N/A</span>";
        
        $bCurrency = acc_Periods::getBaseCurrencyCode();
        $data->row->totalAmount = " <span class='cCode'>{$bCurrency}</span> ";
        $data->row->totalAmount .= core_Type::getByName('double(decimals=2)')->toVerbal($amount);
        
        $data->row->totalAmountReturn = " <span class='cCode'>{$bCurrency}</span> ";
        $data->row->totalAmountReturn .= core_Type::getByName('double(decimals=2)')->toVerbal(abs($amountReturned));
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        $tpl->push('trans/tpl/LineStyles.css', 'CSS');
    }
    
    
    /**
     * Връща броя на документите в посочената линия
     * Може да се филтрират по #state и да се ограничат до maxDocs
     */
    private static function countDocumentsByState($id, $states)
    {
        $states = arr::make($states);
        $query = trans_LineDetails::getQuery();
        $query->where("#lineId = {$id} AND #status != 'removed'");
        $query->in('containerState', $states);
       
        return $query->count();
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        $rec->countReady = $rec->countTotal = 0;
        
        // Изчисляване на готовите и не-готовите редове
        $dQuery = trans_LineDetails::getQuery();
        $dQuery->where("#lineId = {$rec->id}");
        $dQuery->where("#containerState != 'rejected' AND #status != 'removed'");
        $dQuery->show('status,containerState');
        
        while ($dRec = $dQuery->fetch()) {
            $rec->countTotal++;
            if ($dRec->status == 'ready') {
                $rec->countReady++;
            }
        }
        
        // Запис на изчислените полета
        $rec->modifiedOn = dt::now();
        $rec->modifiedBy = core_Users::getCurrent();
        $this->save_($rec, 'countTotal,countReady,modifiedOn,modifiedBy');
        
        // Ако има не-готови линии, нишката се отваря
        $Threads = cls::get('doc_Threads');
        $threadState = ($rec->countReady < $rec->countTotal) ? 'opened' : 'closed';
        $threadRec = doc_Threads::fetch($rec->threadId, 'state');
        $threadRec->state = $threadState;
        $Threads->save($threadRec, 'state');
        $Threads->updateThread($threadRec->id);
    }
    
    
    /**
     * Връща всички избираеми линии
     *
     * @return array $linesArr - масив с опции
     */
    public static function getSelectableLines()
    {
        $linesArr = array();
        $query = self::getQuery();
        $query->where("#state = 'pending'");
        $query->orderBy('id', 'DESC');
        
        $recs = $query->fetchAll();
        array_walk($recs, function ($rec) use (&$linesArr) {
            $linesArr[$rec->id] = trans_Lines::getRecTitle($rec, false);
        });
        
        return $linesArr;
    }
    
    
    /**
     * Изпълнява се преди записа
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if ($rec->__isReplicate) {
            $rec->countReady = 0;
            $rec->countTotal = 0;
        }
    }
    
    
    /**
     * Дефолтни данни, които да се попълват към коментар от документа
     *
     * @param mixed    $rec   - ид или запис
     * @param int|NULL $detId - допълнително ид, ако е нужно
     *
     * @return array $res     - дефолтните данни за коментара
     *               ['subject']     - събджект на коментара
     *               ['body']        - тяло на коментара
     *               ['sharedUsers'] - споделени потребители
     */
    public function getDefaultDataForComment($rec, $detId = null)
    {
        $res = array();
        if (empty($detId)) {
            
            return $res;
        }
        
        $docContainerId = trans_LineDetails::fetchField($detId, 'containerId');
        $Document = doc_Containers::getDocument($docContainerId);
        $documentRec = $Document->fetch('sharedUsers,createdBy,modifiedBy');
        $res['body'] = 'За: #' . $Document->getHandle();

        $users = '';
        $users = keylist::addKey($users, $documentRec->createdBy);
        $users = keylist::addKey($users, $documentRec->modifiedBy);
        $users = keylist::merge($users, $documentRec->sharedUsers);
        $res['sharedUsers'] = $users;

        return $res;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // При промяна на състоянието да се инвалидира, кеша на документите от нея
        if (in_array($rec->state, array('active', 'closed', 'rejected'))) {
            $dQuery = trans_LineDetails::getQuery();
            $dQuery->where("#lineId = {$rec->id}");
            $dQuery->show('containerId');
            while($dRec = $dQuery->fetch()){
                doc_DocumentCache::cacheInvalidation($dRec->containerId);
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'activate' && isset($rec)) {
            if (empty($rec->countTotal)) {
                $requiredRoles = 'no_one';
            } elseif (self::countDocumentsByState($rec->id, 'pending,draft,rejected')) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Затваряне на транспортни линии по разписание
     */
    public function cron_CloseTransLines()
    {
        $activeTime = trans_Setup::get('LINES_ACTIVATED_AFTER');
        $pendingTime = trans_Setup::get('LINES_PENDING_AFTER');

        $activeFrom = dt::addSecs(-1 * $activeTime);
        $pendingFrom = dt::addSecs(-1 * $pendingTime);

        $now = dt::now();
        $query = $this->getQuery();
        $query->where("#state = 'active' || #state = 'pending'");

        while($rec = $query->fetch()){
            if (self::countDocumentsByState($rec->id, 'draft')) continue;

            // Затварят се активните и заявките, на които им е изтекло времето
            if($rec->state == 'active'){
                $date = !empty($rec->activatedOn) ? $rec->activatedOn : $rec->modifiedOn;
                if($date <= $activeFrom){
                    $rec->state = 'closed';
                    $rec->brState = 'active';
                    $this->save($rec, 'state,brState,modifiedOn,modifiedBy');
                    $this->logWrite('Автоматично приключване на активна линия', $rec->id);
                }
            } else {

                // Ако началото е в миналото, и не е бутана дълго време
                if($rec->start <= $now && $rec->modifiedOn <= $pendingFrom){
                    $rec->state = 'closed';
                    $rec->brState = 'pending';
                    $this->save($rec, 'state,brState,modifiedOn,modifiedBy');
                    $this->logWrite('Автоматично приключване на линия на заявка', $rec->id);
                }
            }
        }
    }
}
