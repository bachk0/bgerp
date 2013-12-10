<?php

/**
 * Клас 'techno_ProductsIntf' - Интерфейс за нестандартни арткули
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за нестандартни арткули
 */
class techno_ProductsIntf
{
    
    
    /**
     * Връща информация за ед цена на продукта, отстъпката и таксите
     * @param int $id - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @param double quantity - количество
     * @param datetime $datetime - дата
     * @return stdClass $priceInfo - информация за цената на продукта
     * 				[price]- начална цена
     * 				[discount]  - отстъпка
     * 				[tax]     - нач. такса
     */
    public function getPriceInfo($id, $packagingId = NULL, $quantity = NULL, $datetime = NULL)
    {
    	return $this->class->getPrice($id, $packagingId, $quantity, $datetime);
    }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * @param int $id - ид на продукт
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     * @return stdClass $res - обект с информация за продукта
     * и опаковките му ако $packagingId не е зададено, иначе връща
     * информацията за подадената опаковка
     */
    public static function getProductInfo($id, $packagingId = NULL)
    {
    	return $this->getProductInfo($id, $packagingId);
    }
    
    
    /**
     * Връща ДДС-то на продукта
     * @param int $id - ид на продукт
     * @param date $date - дата
     */
    public static function getVat($id, $date = NULL)
    {
    	return $this->getVat($id, $date);
    }
    
    
	/**
     * Подготвя данните на продукта за показване
     * @param int $id - ид на продукт
     * @return stdClass - обработените данни
     */
    public function prepareData($id)
    {
        return $this->class->prepareData($id);
    }
    
    
	/**
     * Връща вербалното представяне на даденото изделие (HTML, може с картинка)
     * @param stdClass $data - данните на артикула
     * @return text/html - вербално представяне на изделието
     */
    public function renderShortView($data)
    {
        return $this->class->renderShortView($data);
    }
    
    
    /**
     * Връща стойноства на даден параметър на продукта, ако я има
     * @param int $id - ид на продукт
     * @param string $sysId - sysId на параметър
     */
    public function getParam($id, $sysId)
    {
    	return $this->class->getParam($id, $sysId);
    }
}