<?php


/**
 * Мениджър на отчети за Фактури по контрагент
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Счетоводство » Фактури по контрагент
 */
class acc_reports_InvoicesByContragent extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,acc';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'contragent';
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'contragent,checkDate';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,single=none,after=title');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Контрагенти->Група контрагенти,after=contragent,single=none');
        $fieldset->FLD('checkDate', 'date', 'caption=Към дата,after=contragent,mandatory');
        
        $fieldset->FLD('typeOfInvoice', 'enum(out=Изходящи,in=Входящи)', 'caption=Фактури,after=checkDate,maxRadio=2,mandatory');
        $fieldset->FLD('unpaid', 'enum(all=Всички,unpaid=Неплатени)', 'caption=Плащане,after=typeOfInvoice,maxRadio=2,mandatory');
        
        $fieldset->FLD('salesTotalNotPaid', 'double', 'input=none,single=none');
        $fieldset->FLD('salesTotalOverDue', 'double', 'input=none,single=none');
        $fieldset->FLD('salesTotalOverPaid', 'double', 'input=none,single=none');
        $fieldset->FLD('purchaseTotalNotPaid', 'double', 'input=none,single=none');
        $fieldset->FLD('purchaseTotalOverDue', 'double', 'input=none,single=none');
        
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $checkDate = dt::today();
        
        $form->setDefault('checkDate', "{$checkDate}");
        
        $form->setDefault('typeOfInvoice', 'out');
        
        $form->setDefault('unpaid', 'all');
        
        $salesQuery = sales_Sales::getQuery();
        
        $salesQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');
        
        $salesQuery->groupBy('folderId');
        
        $salesQuery->show('folderId, contragentId, folderTitle');
        
        while ($contragent = $salesQuery->fetch()) {
            if (!is_null($contragent->contragentId)) {
                $suggestions[$contragent->folderId] = $contragent->folderTitle;
            }
        }
        
        asort($suggestions);
        
        $form->setSuggestions('contragent', $suggestions);
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $recs = array();
        $isRec = array();
        
        $sRecs = array();
        
        //Избор на поле за групиране
       // $this->groupByField = $rec->unpaid == 'unpaid' ?'className':'contragent';
        
        // Обединяващи продажби
        $salesQuery = sales_Sales::getQuery();
        
        $salesQuery->where("#closedDocuments != ''");
        
        
        // Масив със записи от изходящи фактури
        $invQuery = sales_Invoices::getQuery();
        
        $invQuery->where("#state != 'rejected' AND #number IS NOT NULL");
        
        $invQuery->where(array(
            "#date < '[#1#]'",
            $rec->checkDate . ' 23:59:59'
        ));
        
        //Филтър за КОНТРАГЕНТ и ГРУПИ КОНТРАГЕНТИ
        if ($rec->contragent || $rec->crmGroup) {
            $contragentsArr = array();
            $contragentsId = array();
            
            $invQuery->EXT('coverId', 'doc_Folders', 'externalKey=folderId');
            
            if (!$rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);
                
                foreach ($contragentsArr as $val) {
                    $contragentsId[doc_Folders::fetch($val)->coverId] = doc_Folders::fetch($val)->coverId;
                }
                
                $invQuery->in('coverId', $contragentsId);
            }
            
            if ($rec->crmGroup && !$rec->contragent) {
                $foldersInGroups = self::getFoldersInGroups($rec);
                
                $invQuery->in('folderId', $foldersInGroups);
            }
            
            if ($rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);
                
                foreach ($contragentsArr as $val) {
                    $contragentsId[doc_Folders::fetch($val)->coverId] = doc_Folders::fetch($val)->coverId;
                }
                
                $invQuery->in('coverId', $contragentsId);
                
                $foldersInGroups = self::getFoldersInGroups($rec);
                
                $invQuery->in('folderId', $foldersInGroups);
            }
        }
        
        //Масив с затварящи документи по обединени договори //
        $salesUN = array();
        
        while ($sale = $salesQuery->fetch()) {
            foreach ((keylist::toArray($sale->closedDocuments)) as $v) {
                $salesUN[$v] = ($v);
            }
        }
        
        $salesUN = keylist::fromArray($salesUN);
        
        // Фактури ПРОДАЖБИ
        while ($salesInvoice = $invQuery->fetch()) {
        
        
            $firstDocument = doc_Threads::getFirstDocument($salesInvoice->threadId);
            
            $className = $firstDocument->className;
            
            // Ако са избрани само неплатените фактури
            if($rec->unpaid == 'unpaid'){
                
                $unitedCheck = keylist::isIn($className::fetchField($firstDocument->that), $salesUN);
            
                if (($className::fetchField($firstDocument->that, 'state') == 'closed') &&
                    ($className::fetchField($firstDocument->that, 'closedOn') <= $rec->checkDate) &&
                    ! $unitedCheck) {
                        continue;
                    }
            }
                $threadsId[$salesInvoice->threadId] = $salesInvoice->threadId;
        }
        
        $salesTotalNotPaid = 0;
        $salesTotalOverDue = 0;
        $salesTotalOverPaid = 0;
        
        if (is_array($threadsId)) {
            foreach ($threadsId as $thread) {
                
                // масив от фактури в тази нишка //
                
                $invoicePayments = (deals_Helper::getInvoicePayments($thread, $rec->checkDate));
                
                if (is_array($invoicePayments)) {
                    
                    // фактура от нишката и масив от платежни документи по тази фактура//
                    foreach ($invoicePayments as $inv => $paydocs) {
                        
                        // Ако са избрани само неплатените фактури
                        if($rec->unpaid == 'unpaid'){
                            
                            if (($paydocs->payout >= $paydocs->amount - 0.01) &&
                                ($paydocs->payout <= $paydocs->amount + 0.01)) {
                                    continue;
                                }
                                
                        }
                            $Invoice = doc_Containers::getDocument($inv);
                            
                            if ($Invoice->className != 'sales_Invoices') {
                                continue;
                            }
                            
                            $iRec = $Invoice->fetch(
                                'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,
                                 currencyId,date,dueDate,contragentName'
                                
                                );
                            
                            
                            
                            if (($paydocs->amount - $paydocs->payout) > 0) {
                                $salesTotalNotPaid += ($paydocs->amount - $paydocs->payout);
                            }
                            
                            if (($paydocs->amount - $paydocs->payout) < 0) {
                                $salesTotalOverPaid += - 1 * ($paydocs->amount - $paydocs->payout);
                            }
                            
                            if ($iRec->dueDate && ($paydocs->amount - $paydocs->payout) > 0 &&
                                $iRec->dueDate < $rec->checkDate) {
                                    $salesTotalOverDue += ($paydocs->amount - $paydocs->payout);
                                }
                                
                                // масива с фактурите за показване
                                if (! array_key_exists($iRec->id, $sRecs)) {
                                    $sRecs[$iRec->id] = (object) array(
                                        'threadId' => $thread,
                                        'className' => $Invoice->className,
                                        'invoiceId' => $iRec->id,
                                        'invoiceNo' => $iRec->number,
                                        'invoiceDate' => $iRec->date,
                                        'dueDate' => $iRec->dueDate,
                                        'invoiceContainerId' => $iRec->containerId,
                                        'currencyId' => $iRec->currencyId,
                                        'rate' => $iRec->rate,
                                        'invoiceValue' => $paydocs->amount,
                                        'invoiceVAT' => $iRec->vatAmount,
                                        'invoicePayout' => $paydocs->payout,
                                        'invoiceCurrentSumm' => $paydocs->amount - $paydocs->payout,
                                        'payDocuments' => $paydocs->used,
                                        'contragent'=> $iRec->contragentName
                                    );
                                }
                    }
                }
            }
        }
        
        // Масив със записи от входящи фактури
        $pRecs = array();
        $iRec = array();
        
        $purchasesQuery = purchase_Purchases::getQuery();
        
        $purchasesQuery->where("#closedDocuments != ''");
        
        $pQuery = purchase_Invoices::getQuery();
        
        $pQuery->where("#state != 'rejected' AND #number IS NOT NULL");
        
        $pQuery->where(array(
            "#date < '[#1#]'",
            $rec->checkDate . ' 23:59:59'
        ));
        
        //Филтър за КОНТРАГЕНТ и ГРУПИ КОНТРАГЕНТИ
        if ($rec->contragent || $rec->crmGroup) {
            $contragentsArr = array();
            $contragentsId = array();
            
            $pQuery->EXT('coverId', 'doc_Folders', 'externalKey=folderId');
            
            if (!$rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);
                
                foreach ($contragentsArr as $val) {
                    $contragentsId[doc_Folders::fetch($val)->coverId] = doc_Folders::fetch($val)->coverId;
                }
                
                $invQuery->in('coverId', $contragentsId);
            }
            
            if ($rec->crmGroup && !$rec->contragent) {
                $foldersInGroups = self::getFoldersInGroups($rec);
                
                $invQuery->in('folderId', $foldersInGroups);
            }
            
            if ($rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);
                
                foreach ($contragentsArr as $val) {
                    $contragentsId[doc_Folders::fetch($val)->coverId] = doc_Folders::fetch($val)->coverId;
                }
                
                $invQuery->in('coverId', $contragentsId);
                
                $foldersInGroups = self::getFoldersInGroups($rec);
                
                $invQuery->in('folderId', $foldersInGroups);
            }
        }
        
        
        $purchasesUN = array();
        
        while ($purchase = $purchasesQuery->fetch()) {
            foreach ((keylist::toArray($purchase->closedDocuments)) as $v) {
                $purchasesUN[$v] = ($v);
            }
        }
        
        $purchasesUN = keylist::fromArray($purchasesUN);
        
        
        // Фактури ПОКУПКИ
        while ($purchaseInvoices = $pQuery->fetch()) {
            
            $firstDocument = doc_Threads::getFirstDocument($purchaseInvoices->threadId);
            
            $className = $firstDocument->className;
            
            $purUnitedCheck = keylist::isIn($className::fetchField($firstDocument->that), $purchasesUN);
            
            // Ако са избрани само неплатените фактури
            if($rec->unpaid == 'unpaid'){
          
                if (($className::fetchField($firstDocument->that, 'state') == 'closed') &&
                    ($className::fetchField($firstDocument->that, 'closedOn') <= $rec->checkDate) &&
                    ! $purUnitedCheck) {
                        continue;
                    }
            }
            
                $pThreadsId[$purchaseInvoices->threadId] = $purchaseInvoices->threadId;
                
        }
        
        $purchaseTotalNotPaid = 0;
        $purchaseTotalOverDue = 0;
        
        if (is_array($pThreadsId)) {
            foreach ($pThreadsId as $pThread) {
                
                // масив от фактури в тази нишка //
                
                $pInvoicePayments = (deals_Helper::getInvoicePayments($pThread, $rec->checkDate));
                
                if ((is_array($pInvoicePayments))) {
                    
                    // фактура от нишката и масив от платежни документи по тази фактура//
                    foreach ($pInvoicePayments as $pInv => $paydocs) {
                        
                        // Ако са избрани само неплатените фактури
                        if($rec->unpaid == 'unpaid'){
                            
                            if (($paydocs->payout >= $paydocs->amount - 0.01) &&
                                ($paydocs->payout <= $paydocs->amount + 0.01)) {
                                    continue;
                                }
                        }
                            $Invoice = doc_Containers::getDocument($pInv);
                            
                            if ($Invoice->className != 'purchase_Invoices') {
                                continue;
                            }
                            
                            $iRec = $Invoice->fetch(
                                'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,
                                 currencyId,date,dueDate,contragentName'
                                
                                );
                            
                            if (($paydocs->amount - $paydocs->payout) > 0) {
                                $purchaseTotalNotPaid += ($paydocs->amount - $paydocs->payout);
                            }
                            
                            if ($iRec->dueDate && ($paydocs->amount - $paydocs->payout) > 0 &&
                                $iRec->dueDate < $rec->checkDate) {
                                    $purchaseTotalOverDue += ($paydocs->amount - $paydocs->payout);
                                }
                                
                                // масива с фактурите за показване
                                if (! array_key_exists($iRec->id, $pRecs)) {
                                    $pRecs[$iRec->id] = (object) array(
                                        'threadId' => $pThread,
                                        'className' => $Invoice->className,
                                        'invoiceId' => $iRec->id,
                                        'invoiceNo' => $iRec->number,
                                        'invoiceDate' => $iRec->date,
                                        'dueDate' => $iRec->dueDate,
                                        'invoiceContainerId' => $iRec->containerId,
                                        'currencyId' => $iRec->currencyId,
                                        'rate' => $iRec->rate,
                                        'invoiceValue' => $paydocs->amount,
                                        'invoiceVAT' => $iRec->vatAmount,
                                        'invoicePayout' => $paydocs->payout,
                                        'invoiceCurrentSumm' => $paydocs->amount - $paydocs->payout,
                                        'payDocuments' => $paydocs->used,
                                        'contragent'=> $iRec->contragentName
                                    );
                                }
                    }
                }
            }
        }
        
        if ($rec->unpaid == 'unpaid'){
            
            $rec->salesTotalNotPaid = $salesTotalNotPaid;
            
            $rec->salesTotalOverDue = $salesTotalOverDue;
            
            $rec->salesTotalOverPaid = $salesTotalOverPaid;
            
            $rec->purchaseTotalNotPaid = $purchaseTotalNotPaid;
            
            $rec->purchaseTotalOverDue = $purchaseTotalOverDue;
            
        }
        if (count($sRecs)) {
            arr::sortObjects($sRecs, 'invoiceDate', 'asc', 'stri');
        }
        
        if (count($pRecs)) {
            arr::sortObjects($sRecs, 'invoiceDate', 'asc', 'stri');
        }
        
        $recs = $rec->typeOfInvoice == 'out' ? $sRecs : $pRecs;
        
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($export === false) {
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,smartCenter');
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
            $fld->FLD('invoiceDate', 'varchar', 'caption=Дата');
            $fld->FLD('dueDate', 'varchar', 'caption=Краен срок');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
            $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->Сума,smartCenter');
            $fld->FLD('paidDates', 'varchar', 'caption=Платено->Плащания,smartCenter');
            $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Състояние->Неплатено');
            $fld->FLD('invoiceOverSumm', 'double(smartRound,decimals=2)', 'caption=Състояние->Надплатено');
        } else {
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,smartCenter');
            $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
            $fld->FLD('invoiceDate', 'date', 'caption=Дата,smartCenter');
            $fld->FLD('dueDate', 'date', 'caption=Краен срок,smartCenter');
            $fld->FLD('dueDateStatus', 'varchar', 'caption=Състояние,smartCenter');
            $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
            $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
            $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->сума');
            $fld->FLD('paidDates', 'varchar', 'caption=Платено->плащания,smartCenter');
            $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Състояние->Неплатено');
            $fld->FLD('invoiceOverSumm', 'double(smartRound,decimals=2)', 'caption=Състояние->Надплатено');
        }
        
        return $fld;
    }
    
    
    /**
     * Връща платена сума
     *
     * @param stdClass $dRec
     * @param bool     $verbal
     *
     * @return mixed $paidAmount
     */
    private static function getPaidAmount($dRec, $verbal = true)
    {
        $paidAmount = $dRec->invoicePayout;
        
        return $paidAmount;
    }
    
    
    /**
     * Връща дати на плащания
     *
     * @param stdClass $dRec
     * @param bool     $verbal
     *
     * @return mixed $paidDates$data->rec->salesTotalNotPaid
     */
    private static function getPaidDates($dRec, $verbal = true)
    {
        if (is_array($dRec->payDocuments)) {
            foreach ($dRec->payDocuments as $onePayDoc) {
                if (! is_null($onePayDoc->containerId)) {
                    $Document = doc_Containers::getDocument($onePayDoc->containerId);
                } else {
                    continue;
                }
                $payDocClass = $Document->className;
                
                $paidDatesList .= ',' . $payDocClass::fetch($Document->that)->valior;
            }
        }
        if ($verbal === true) {
            $amountsValiors = explode(',', trim($paidDatesList, ','));
            
            foreach ($amountsValiors as $v) {
                $paidDate = dt::mysql2verbal($v, $mask = 'd.m.y');
                
                $paidDates .= "${paidDate}" . '<br>';
            }
        } else {
            $amountsValiors = explode(',', trim($paidDatesList, ','));
            
            foreach ($amountsValiors as $v) {
                $paidDate = dt::mysql2verbal($v, $mask = 'd.m.y');
                
                $paidDates .= "${paidDate}" . "\n\r";
            }
        }
        
        return $paidDates;
    }
    
    
    /**
     * Връща просрочие на плащане
     *
     * @param stdClass $dRec
     * @param bool     $verbal
     *
     * @return mixed $dueDate
     */
    private static function getDueDate($dRec, $verbal = true, $rec)
    {
        if ($verbal === true) {
            if ($dRec->dueDate) {
                $dueDate = dt::mysql2verbal($dRec->dueDate, $mask = 'd.m.Y');
                
                if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
                    $dueDate = ht::createHint($dueDate, 'фактурата е просрочена', 'warning');
                }
            } else {
                $dueDate = '';
            }
        } else {
            if ($dRec->dueDate) {
                $dueDate = $dRec->dueDate;
            } else {
                $dueDate = '';
            }
        }
        
        return $dueDate;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        
        $row = new stdClass();
        
        $invoiceNo = str_pad($dRec->invoiceNo, 10, '0', STR_PAD_LEFT);
        
        $row->invoiceNo = ht::createLinkRef(
            
            $invoiceNo,
            array(
                $dRec->className,
                'single',
                $dRec->invoiceId
            )
            
            );
        
        $row->contragent = ($dRec->contragent);
        
        $row->invoiceDate = $Date->toVerbal($dRec->invoiceDate);
        
        $row->dueDate = self::getDueDate($dRec, true, $rec);
        
        $row->currencyId = $dRec->currencyId;
        
        $invoiceValue = $dRec->invoiceValue + $dRec->invoiceVat;
        
        $row->invoiceValue = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceValue);
        
        if ($dRec->invoiceCurrentSumm > 0) {
            $row->invoiceCurrentSumm = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->invoiceCurrentSumm);
        }
        
        if ($dRec->invoiceCurrentSumm < 0) {
            $invoiceOverSumm = - 1 * $dRec->invoiceCurrentSumm;
            $row->invoiceOverSumm = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceOverSumm);
        }
        $row->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal(self::getPaidAmount($dRec));
        
        $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, true) . '</span>';
        
        if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0) {
            $row->ROW_ATTR['class'] = 'bold red';
        }
        
        if ($dRec->className == 'sales_Invoices') {
            $row->className = 'Фактури ПРОДАЖБИ';
        } else {
            $row->className = 'Фактури ПОКУПКИ';
        }
        
        return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(
            tr(
                "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info red '><legend class='groupTitle'><small><b>|СПРАВКАТА Е В ПРОЦЕС НА РАЗРАБОТКА !!!|*</b></small></legend>
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN contragent-->|Контрагент|*: <b>[#contragent#]</b><!--ET_END contragent--></div></small>
                                <small><div><!--ET_BEGIN salesTotalNotPaid-->|фактури ПРОДАЖБИ »    НЕПЛАТЕНИ|*: <b>[#salesTotalNotPaid#]</b><!--ET_END salesTotalNotPaid--></div></small>
                                <small><div><!--ET_BEGIN salesTotalOverDue-->|фактури ПРОДАЖБИ »   ПРОСРОЧЕНИ|*: <b>[#salesTotalOverDue#]</b><!--ET_END salesTotalOverDue--></div></small>
                                <small><div><!--ET_BEGIN salesTotalOverPaid-->|фактури ПРОДАЖБИ »   НАДПЛАТЕНИ|*: <b>[#salesTotalOverPaid#]</b><!--ET_END salesTotalOverPaid--></div></small>
                                <small><div><!--ET_BEGIN purchaseTotalNotPaid-->|фактури ПОКУПКИ »  НЕПЛАТЕНИ|*: <b>[#purchaseTotalNotPaid#]</b><!--ET_END purchaseTotalNotPaid--></div></small>
                                <small><div><!--ET_BEGIN purchaseTotalOverDue-->|фактури ПОКУПКИ » ПРОСРОЧЕНИ|*: <b>[#purchaseTotalOverDue#]</b><!--ET_END purchaseTotalOverDue--></div></small>
                                </fieldset><!--ET_END BLOCK-->"
                )
            );
        
//         if (isset($data->rec->contragent) || isset($data->rec->crmGroup)) {
//             $marker = 0;
//             if (isset($data->rec->crmGroup)) {
//                 foreach (type_Keylist::toArray($data->rec->crmGroup) as $group) {
//                     $marker++;
                    
//                     $groupVerb .= (crm_Groups::getTitleById($group));
                    
//                     if ((count((type_Keylist::toArray($data->rec->crmGroup))) - $marker) != 0) {
//                         $groupVerb .= ', ';
//                     }
//                 }
                
//                 $fieldTpl->append('<b>' . $groupVerb . '</b>', 'crmGroup');
//             }
            
//             $marker = 0;
            
//             if (isset($data->rec->contragent)) {
//                 foreach (type_Keylist::toArray($data->rec->contragent) as $contragent) {
//                     $marker++;
                    
//                     $contragentVerb .= (doc_Folders::getTitleById($contragent));
                    
//                     if ((count(type_Keylist::toArray($data->rec->contragent))) - $marker != 0) {
//                         $contragentVerb .= ', ';
//                     }
//                 }
                
//                 $fieldTpl->append('<b>' . $contragentVerb . '</b>', 'contragent');
//             }
//         } else {
//             $fieldTpl->append('<b>' . 'Всички' . '</b>', 'contragent');
//         }
      
       
            if (isset($data->rec->salesTotalNotPaid)) {
                $fieldTpl->append(
                    core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesTotalNotPaid),
                    'salesTotalNotPaid'
                    );
            }
            
            if (isset($data->rec->salesTotalOverDue)) {
                $fieldTpl->append(
                    core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesTotalOverDue),
                    'salesTotalOverDue'
                    );
            }
            
            if (isset($data->rec->salesTotalOverPaid)) {
                $fieldTpl->append(
                    core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesTotalOverPaid),
                    'salesTotalOverPaid'
                    );
            }
            
            if (isset($data->rec->purchaseTotalNotPaid)) {
                $fieldTpl->append(
                    core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->purchaseTotalNotPaid),
                    'purchaseTotalNotPaid'
                    );
            }
            
            if (isset($data->rec->purchaseTotalOverDue)) {
                $fieldTpl->append(
                    core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->purchaseTotalOverDue),
                    'purchaseTotalOverDue'
                    );
            }
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->paidAmount = (self::getPaidAmount($dRec));
        
        $res->paidDates = self::getPaidDates($dRec, false);
        
        $res->dueDate = self::getDueDate($dRec, false, $rec);
        
        if ($dRec->invoiceCurrentSumm < 0) {
            $invoiceOverSumm = - 1 * $dRec->invoiceCurrentSumm;
            $res->invoiceCurrentSumm = '';
            $res->invoiceOverSumm = ($invoiceOverSumm);
        }
        
        if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
            $res->dueDateStatus = 'Просрочен';
        }
        
        $invoiceNo = str_pad($dRec->invoiceNo, 10, '0', STR_PAD_LEFT);
        
        $res->invoiceNo = $invoiceNo;
    }
}
