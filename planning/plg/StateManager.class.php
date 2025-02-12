<?php


/**
 * Плъгин добавящ към документ следните състояние: чернова, чакащо, аквитно, приключено, спряно, оттеглено и събудено
 * и управляващ преминаването им от едно в друго
 *
 * Преминаванията от състояние в състояние са следните:
 *
 * Чернова    (draft)    -> чакащо, активно или оттеглено
 * Чакащо     (waiting)  -> активно или оттеглено
 * Активно    (active)   -> спряно, приключено или оттеглено
 * Приключено (closed)   -> събудено или оттеглено
 * Спряно     (stopped)  -> активно или събудено
 * Събудено   (wakeup)   -> приключено, спряно или оттеглено
 * Оттеглено  (rejected) -> възстановено до някое от горните състояния
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_plg_StateManager extends core_Plugin
{
    /**
     * За кои действия да се изисква основание
     */
    public $demandReasonChangeState;
    
    
    /**
     * Масив със състояниет, за които да се праща нотификация
     */
    public $notifyActionNamesArr = array();
    
    
    /**
     * Масив със състояния, за които да се изтрива предишната нотификация
     */
    public $removeOldNotifyStatesArr = array();
    
    
    /**
     * Дали ключа на нотификацията да сочи към нишката или документа - за уникалност на нотификацията
     */
    public $notifyToThread = true;
    
    
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Ако липсва, добавяме поле за състояние
        if (!$mvc->fields['state']) {
            $mvc->FLD('state', 'enum(draft=Чернова, pending=Заявка,waiting=Чакащо,active=Активирано, rejected=Оттеглено, closed=Приключено, stopped=Спряно, wakeup=Събудено,template=Шаблон)', 'caption=Състояние, input=none');
        }
        
        if (!$mvc->fields['timeClosed']) {
            $mvc->FLD('timeClosed', 'datetime(format=smartTime)', 'caption=Времена->Затворено на,input=none');
        }
        
        if (isset($mvc->demandReasonChangeState)) {
            $mvc->demandReasonChangeState = arr::make($mvc->demandReasonChangeState, true);
        }

        if (!$mvc->fields['lastChangeStateOn']) {
            $mvc->FLD('lastChangeStateOn', 'datetime(format=smartTime)', 'caption=Промяна състояние->На,input=none');
        }

        if (!$mvc->fields['lastChangeStateBy']) {
            $mvc->FLD('lastChangeStateBy', 'key(mvc=core_Users,select=nick)', 'caption=Промяна състояние->От на,input=none');
        }

        $mvc->setDbIndex('timeClosed');
    }
    
    
    /**
     * Ще има ли предупреждение при смяна на състоянието
     */
    public static function on_AfterGetChangeStateWarning($mvc, &$res, $rec, $newState)
    {
        if ($newState == 'closed') {
            $res = 'Сигурни ли сте, че искате да приключите документа|*?';
        } elseif ($newState == 'wakeup') {
            $res = 'Сигурни ли сте, че искате да събудите документа|*?';
        } elseif ($newState == 'stopped') {
            $res = 'Сигурни ли сте, че искате да спрете документа|*?';
        } else {
            $res = 'Сигурни ли сте, че искате да активирате документа|*?';
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = &$data->rec;
        
        // Добавяне на бутон за приключване
        if ($mvc->haveRightFor('close', $rec)) {
            $warning = $mvc->getChangeStateWarning($rec, 'closed');
            $attr = array('ef_icon' => 'img/16/gray-close.png', 'title' => 'Приключване на документа', 'warning' => $warning, 'order' => 30);
            $attr['id'] = 'btnClose';
            
            if (isset($mvc->demandReasonChangeState, $mvc->demandReasonChangeState['close'])) {
                unset($attr['warning']);
            }
            
            $closeError = $mvc->getCloseBtnError($rec);
            if (!empty($closeError)) {
                $attr['error'] = $closeError;
                unset($attr['warning']);
            }
            
            $data->toolbar->addBtn('Приключване', array($mvc, 'changeState', $rec->id, 'type' => 'close', 'ret_url' => true), $attr);
        }
        
        // Добавяне на бутон за спиране
        if ($mvc->haveRightFor('stop', $rec)) {
            $warning = $mvc->getChangeStateWarning($rec, 'stopped');
            
            $attr = array('ef_icon' => 'img/16/control_pause.png', 'title' => 'Спиране на документа', 'warning' => $warning, 'order' => 30, 'row' => 2);
            if (isset($mvc->demandReasonChangeState, $mvc->demandReasonChangeState['stop'])) {
                unset($attr['warning']);
            }
            
            $data->toolbar->addBtn('Пауза', array($mvc, 'changeState', $rec->id, 'type' => 'stop', 'ret_url' => true), $attr);
        }
        
        // Добавяне на бутон за събуждане
        if ($mvc->haveRightFor('wakeup', $rec)) {
            $warning = $mvc->getChangeStateWarning($rec, 'wakeup');
            
            $attr = array('ef_icon' => 'img/16/lightbulb.png', 'title' => 'Събуждане на документа','warning' => $warning, 'order' => 30, 'row' => 3);
            if (isset($mvc->demandReasonChangeState, $mvc->demandReasonChangeState['wakeup'])) {
                unset($attr['warning']);
            }
            
            $data->toolbar->addBtn('Събуждане', array($mvc, 'changeState', $rec->id, 'type' => 'wakeup', 'ret_url' => true), $attr);
        }
        
        // Добавяне на бутон за активиране от различно от чернова състояние
        if ($mvc->haveRightFor('activateAgain', $rec)) {
            $warning = $mvc->getChangeStateWarning($rec, null);
            
            $attr = array('ef_icon' => 'img/16/control_play.png', 'title' => 'Пускане на документа','warning' => $warning, 'order' => 30);
            if (isset($mvc->demandReasonChangeState, $mvc->demandReasonChangeState['activateAgain'])) {
                unset($attr['warning']);
            }
            
            $data->toolbar->addBtn('Пускане', array($mvc, 'changeState', $rec->id, 'type' => 'activateAgain', 'ret_url' => true, ), $attr);
        }
        
        // Добавяне на бутон запървоначално активиране
        if ($mvc->haveRightFor('activate', $rec)) {
            $warning = $mvc->getChangeStateWarning($rec, 'active');
            
            $attr = array('ef_icon' => 'img/16/lightning.png', 'title' => 'Активиране на документа', 'warning' => $warning, 'order' => 30, 'id' => 'btnActivate');
            if (isset($mvc->demandReasonChangeState, $mvc->demandReasonChangeState['activate'])) {
                unset($attr['warning']);
            }

            $errMsg = null;
            $data->toolbar->addBtn('Активиране', array($mvc, 'changeState', $rec->id, 'type' => 'activate', 'ret_url' => true, ), $attr);
            if(!$mvc->activateNow($rec, $errMsg)){
                $data->toolbar->setError('btnActivate', $errMsg);
            }
        }
        
        // Бутон за заявка
        if ($mvc->haveRightFor('pending', $rec)) {
            if ($rec->state != 'pending') {
                $r = $data->toolbar->haveButton('btnActivate') ? 2 : 1;
                $data->toolbar->addBtn('Заявка', array($mvc, 'changePending', $rec->id), "id=btnRequest,warning=Наистина ли желаете документът да стане заявка?,row={$r}", 'ef_icon = img/16/tick-circle-frame.png,title=Превръщане на документа в заявка');
            } else {
                $data->toolbar->addBtn('Чернова', array($mvc, 'changePending', $rec->id), 'id=btnDraft,warning=Наистина ли желаете да върнете възможността за редакция?', 'ef_icon = img/16/arrow-undo.png,title=Връщане на възможността за редакция');
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'close' || $action == 'stop' || $action == 'wakeup' || $action == 'activateagain' || $action == 'activate') && isset($rec)) {
            switch ($action) {
                case 'close':
                    
                    // Само активните, събудените и спрените могат да бъдат приключени
                    if (!in_array($rec->state, array('active', 'wakeup', 'stopped'))) {
                        $requiredRoles = 'no_one';
                    }

                    if ($rec->state == 'rejected' || $rec->state == 'draft' || $rec->state == 'closed') {
                        $requiredRoles = 'no_one';
                    }
                    break;
                case 'stop':
                    
                    // Само активните могат да бъдат спрени
                    if ($rec->state != 'active' && $rec->state != 'wakeup') {
                        $requiredRoles = 'no_one';
                    }
                    break;
                case 'wakeup':
                    
                    // Само приключените могат да бъдат събудени
                    if ($rec->state != 'closed') {
                        $requiredRoles = 'no_one';
                    }
                    break;
                case 'activateagain':
                    
                    // Дали може да бъде активирана отново, след като е било променено състоянието
                    if ($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'wakeup' || $rec->state == 'rejected' || $rec->state == 'draft' || $rec->state == 'waiting' || $rec->state == 'template' || $rec->state == 'pending') {
                        $requiredRoles = 'no_one';
                    }
                    break;
                case 'activate':
                    
                    // Само приключените могат да бъдат събудени
                    if (($rec->state != 'draft' && $rec->state != 'pending' && $rec->state != 'waiting') && isset($rec->state)) {
                        $requiredRoles = 'no_one';
                    }
                    break;
            }
            
            if ($requiredRoles != 'no_one') {
                
                // Минимални роли за промяна на състоянието
                $requiredRoles = $mvc->getRequiredRoles('changestate', $rec);
            }
        }
        
        if ($action == 'reject' && isset($rec)) {
            if ($rec->state == 'stopped') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Преди изпълнението на контролерен екшън
     *
     * @param core_Manager $mvc
     * @param core_ET      $res
     * @param string       $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if (strtolower($action) == 'changestate') {
            $mvc->requireRightFor('changestate');
            
            expect($id = Request::get('id', 'int'));
            expect($rec = $mvc->fetch($id));
            expect($action = Request::get('type', 'enum(close,stop,wakeup,activateAgain,activate)'));
            
            // Проверяваме правата за съответното действие затваряне/активиране/спиране/събуждане
            $mvc->requireRightFor($action, $rec);
            
            if (isset($mvc->demandReasonChangeState)) {
                if (in_array($action, $mvc->demandReasonChangeState)) {
                    if (!$reason = Request::get('reason', 'text')) {
                        $res = self::getReasonForm($mvc, $action, $rec);
                        
                        return false;
                    }
                    $rec->_reason = $reason;
                }
            }
            
            if ($action == 'close') {
                $closeError = $mvc->getCloseBtnError($rec);
                expect(empty($closeError));
            }
            
            static::changeState($mvc, $rec, $action);
            
            // Редирект обратно към документа
            redirect(array($mvc, 'single', $rec->id));
        }
    }


    /**
     * Променя състоянието на документа
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param string $action
     * @return void
     */
    public static function changeState($mvc, $rec, $action)
    {
        $logAction = null;
        $now = dt::now();
        $cu = core_Users::getCurrent();
        switch ($action) {
            case 'close':
                $rec->brState = $rec->state;
                $rec->state = 'closed';
                $rec->timeClosed = $now;
                $logAction = 'Приключване';
                break;
            case 'stop':
                $rec->brState = $rec->state;
                $rec->state = 'stopped';
                $logAction = 'Спиране';

                break;
            case 'wakeup':
                $rec->brState = $rec->state;
                $rec->state = 'wakeup';
                $rec->timeClosed = null;
                $logAction = 'Събуждане';
                break;
            case 'activateAgain':
                $rec->state = $rec->brState;
                $rec->brState = 'stopped';
                $logAction = ($rec->state == 'wakeup') ? 'Събуждане' : 'Пускане';
                break;
            case 'activate':
                $activateErrMsg = null;
                $rec->brState = $rec->state;
                $rec->state = ($mvc->activateNow($rec, $activateErrMsg)) ? 'active' : 'waiting';
                $logAction = ($rec->state == 'active') ? 'Активиране' : 'Преминаване в чакащо';
                break;
        }

        // Ако ще активираме: запалваме събитие, че ще активираме
        $saveFields = 'brState,state,modifiedOn,modifiedBy,timeClosed,lastChangeStateOn,lastChangeStateBy';
        if($mvc instanceof planning_Tasks){
            $saveFields .= ",orderByAssetId";
        }

        $rec->lastChangeStateOn = $now;
        $rec->lastChangeStateBy = $cu;

        if ($action == 'activate' && empty($activateErrMsg)) {
            $rec->activatedBy = $cu;
            $rec->activatedOn = $now;
            $mvc->invoke('BeforeActivation', array(&$rec));
            $saveFields = null;
        }

        // Обновяваме състоянието и старото състояние
        if ($mvc->save($rec, $saveFields)) {
            $mvc->logWrite($logAction, $rec->id);
            $mvc->invoke('AfterChangeState', array(&$rec, $rec->state));
        }

        // Ако сме активирали: запалваме събитие, че сме активирали
        if ($action == 'activate') {
            if(empty($activateErrMsg)){
                $mvc->invoke('AfterActivation', array(&$rec));
            } else {
                core_Statuses::newStatus($activateErrMsg, 'warning');
            }
        }
    }


    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        $mvc->invoke('AfterChangeState', array(&$rec, 'rejected'));

        $rec->lastChangeStateOn = dt::now();
        $rec->lastChangeStateBy = core_Users::getCurrent();
        $mvc->save_($rec, 'lastChangeStateOn,lastChangeStateBy');
    }
    
    
    /**
     * След промяна на състоянието
     */
    protected static function on_AfterChangeState($mvc, &$rec, $action)
    {
        $action = strtolower($action);
        if ($mvc->notifyActionNamesArr && ($caption = $mvc->notifyActionNamesArr[$action])) {
            
            // Абонираните потребители към документа
            $notifyArr = doc_Containers::getSubscribedUsers($rec->containerId, true, true);
            
            // Възможност за спиране/пускане на нотификациите за заявка в папка
            $fKey = doc_Folders::getSettingsKey($rec->folderId);
            $stateChangeNotifications = core_Settings::fetchUsers($fKey, 'stateChange');
            foreach ((array) $stateChangeNotifications as $userId => $stateChange) {
                if ($stateChange['stateChange'] == 'no') {
                    unset($notifyArr[$userId]);
                } elseif ($stateChange['stateChange'] == 'yes') {
                    // Може да е абониран, но да няма права
                    if ($mvc->haveRightFor('single', $rec, $userId)) {
                        $notifyArr[$userId] = $userId;
                    }
                }
            }
            
            if (empty($notifyArr)) {
                return ;
            }
            
            $caption = str::mbUcfirst($caption);
            
            $name = $mvc->getRecTitle($rec);
            
            $removeOldNotify = false;
            if ($mvc->removeOldNotifyStatesArr) {
                $mvc->removeOldNotifyStatesArr = arr::make($mvc->removeOldNotifyStatesArr, true);
                
                if ($mvc->removeOldNotifyStatesArr[$action]) {
                    $removeOldNotify = true;
                }
            }
            
            $notifyToThread = true;
            if ($mvc->notifyToThread === false) {
                $notifyToThread = false;
            }
            
            $msg = "|{$caption} на|* \"{$name}\"";
            doc_Containers::notifyToSubscribedUsers($rec->containerId, $msg, $removeOldNotify, $notifyToThread, $notifyArr);
        }
    }
    
    
    /**
     * След възстановяване
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if ($rec->state != 'rejected') {
            $mvc->invoke('AfterChangeState', array(&$rec, 'restore'));
        }

        $rec->lastChangeStateOn = dt::now();
        $rec->lastChangeStateBy = core_Users::getCurrent();
        $mvc->save_($rec, 'lastChangeStateOn,lastChangeStateBy');
    }
    
    
    /**
     * Дефолт имплементация на метода за намиране на състоянието, в
     * което да влиза документа при активиране
     */
    public static function on_AfterActivateNow($mvc, &$res, $rec, &$msg)
    {
        // По дефолт при активиране ще се преминава в активно състояние
        if (is_null($res)) {
            $res = true;
        }
    }
    
    
    /**
     * Подготовка на формата за добавяне на основание към смяната на състоянието
     *
     * @param core_Mvc $mvc
     * @param string   $action
     * @param stdClass $rec
     *
     * @return core_Form $res
     */
    private static function getReasonForm($mvc, $action, $rec)
    {
        $actionArr = array('close' => 'Приключване', 'stop' => 'Спиране', 'activateAgain' => 'Пускане', 'activate' => 'Активиране', 'wakeup' => 'Събуждане');
        
        $form = cls::get('core_Form');
        $form->FLD('reason', 'text(rows=2)', 'caption=Основание,mandatory');
        $actionVerbal = strtr($action, $actionArr);
        $form->title = $actionVerbal . '|* ' . tr('на') . '|* ' . planning_Jobs::getHyperlink($rec->id, true);
        $form->input();
        
        if ($form->isSubmitted()) {
            $url = array($mvc, 'changeState', $rec->id, 'type' => $action, 'reason' => $form->rec->reason);
            
            redirect($url);
        }
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $res = $form->renderHtml();
        $res = $mvc->renderWrapping($res);
        
        return $res;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $rec = &$data->rec;
        $row = &$data->row;
        
        if ($rec->state == 'stopped' || $rec->state == 'closed') {
            $tpl = new ET(' ' . tr('от|* [#user#] |на|* [#date#]'));
            
            $dateChanged = ($rec->state == 'closed') ? $rec->timeClosed : $rec->modifiedOn;
            setIfNot($dateChanged, $rec->modifiedOn);
            $row->state .= $tpl->placeArray(array('user' => $row->modifiedBy, 'date' => dt::mysql2Verbal($dateChanged)));
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        $rec = $data->form->rec;
        if ($mvc->haveRightFor('activate', $rec)) {
            $data->form->toolbar->addSbBtn('Активиране', 'active', 'id=activate, order=9.99980', 'ef_icon = img/16/lightning.png,title=Активиране на документа');
        }
    }
    

    /**
     * Ако е натиснат бутона 'Активиране" добавя състоянието 'active' в $form->rec
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        if ($form->isSubmitted()) {
            if ($form->cmd == 'active') {
                $msg = null;
                $rec->state = ($mvc->activateNow($rec, $msg)) ? 'active' : 'waiting';
                if($rec->state == 'active'){
                    $mvc->invoke('BeforeActivation', array($rec));
                    $form->rec->_isActivated = true;
                } else {
                    core_Statuses::newStatus($msg, 'warning');
                }
            } elseif($form->cmd == 'save' && isset($rec->id)){
                if($rec->state == 'active'){
                    $msg = null;
                    $rec->state = ($mvc->activateNow($rec, $msg)) ? 'active' : 'waiting';
                    if($msg){
                        core_Statuses::newStatus($msg, 'warning');
                        $mvc->logWrite('Преминаване в чакащо', $rec->id);
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
        if ($rec->_isActivated === true) {
            unset($rec->_isActivated);
            $mvc->invoke('AfterActivation', array($rec));
            $mvc->logWrite('Активиране', $rec->id);
        }
    }


    /**
     * Преди запис на документ
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if(empty($rec->id)){
            $rec->lastChangeStateOn = dt::now();
            $rec->lastChangeStateBy = core_Users::getCurrent();
        }
    }


    /**
     * След намиране на текста за грешка на бутона за 'Приключване'
     */
    public static function on_AfterGetCloseBtnError($mvc, &$res, $rec)
    {
        $res = (!empty($res)) ? $res : null;
    }
}
