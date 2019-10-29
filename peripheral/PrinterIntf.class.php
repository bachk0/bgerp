<?php


/**
 * Интерфейс за отпечатване на принтер
 *
 * @category  bgerp
 * @package   peripheral
 *
 * @author    Yusein Yuseinov
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_PrinterIntf extends peripheral_DeviceIntf
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Отпечатва подадени текст
     * 
     * @param stdClass $rec
     * @param string $text
     */
    public function getJS($rec, $text)
    {
        return $this->class->getJS($rec, $text);
    }
}
