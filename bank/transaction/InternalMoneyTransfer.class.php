<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа bank_InternalMoneyTransfer
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class bank_transaction_InternalMoneyTransfer
{
    
    
    /**
     * 
     * @var bank_InternalMoneyTransfer
     */
    public $class;


    /**
     * Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     * Създава транзакция която се записва в Журнала, при контирането
     *
     * Ако избраната валута е в основна валута
     *
     * Dt: 501. Каси                     (Каса, Валута)
     * 503. Разплащателни сметки    (Банкова сметка, Валута)
     *
     * Ct: 503. Разплащателни сметки    (Банкова сметка, Валута)
     *
     * Ако е в друга валута различна от основната
     *
     * Dt: 501. Каси                              (Каса, Валута)
     * 503. Разплащателни сметки             (Банкова сметка, Валута)
     *
     * Ct: 481. Разчети по курсови разлики         (Валута)
     *
     * Dt: 481. Разчети по курсови разлики         (Валута)
     * Ct: 503. Разплащателни сметки    (Банкова сметка, Валута)
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    
    	($rec->debitCase) ? $debitArr = array('cash_Cases', $rec->debitCase) : $debitArr = array('bank_OwnAccounts', $rec->debitBank);
    	$currencyCode = currency_Currencies::getCodeById($rec->currencyId);
    	$amount = currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $currencyCode);
    
    	$fromBank = array($rec->creditAccId,
    			array('bank_OwnAccounts', $rec->creditBank),
    			array('currency_Currencies', $rec->currencyId),
    			'quantity' => $rec->amount);
    
    	$toArr = array($rec->debitAccId,
    			$debitArr,
    			array('currency_Currencies', $rec->currencyId),
    			'quantity' => $rec->amount);
    
    	if($rec->currencyId == acc_Periods::getBaseCurrencyId($rec->valior)){
    		$entry = array('amount' => $amount, 'debit' => $toArr, 'credit' => $fromBank);
    		$entry = array($entry);
    	} else {
    		$entry = array();
    		$entry[] = array('amount' => $amount, 'debit' => $toArr, 'credit' => array('481', array('currency_Currencies', $rec->currencyId), 'quantity' => $rec->amount));
    		$entry[] = array('amount' => $amount, 'debit' => array('481', array('currency_Currencies', $rec->currencyId), 'quantity' => $rec->amount), 'credit' => $fromBank);
    	}
    
    	// Подготвяме информацията която ще записваме в Журнала
    	$result = (object)array(
    			'reason' => $rec->reason,   // основанието за ордера
    			'valior' => $rec->valior,   // датата на ордера
    			'entries' => $entry,
    	);
    
    	return $result;
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function finalizeTransaction($id)
    {
    	$rec = $this->class->fetchRec($id);
    	$rec->state = 'closed';
    
    	return $this->class->save($rec);
    }
}