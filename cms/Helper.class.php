<?php


/**
 * Библиотечен клас за външната част
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_Helper extends core_BaseClass
{
    /**
     * Има ли потребител с имейл от същия брид
     *
     * @return int|NULL $userId - ид на потребител или NULL, ако няма
     */
    public static function getUserFromSameBrid()
    {
        $valsArr = log_Browsers::getVars(array('email'));
        if (empty($valsArr['email'])) {
            
            return;
        }
        
        $userId = core_Users::getUserByEmail($valsArr['email']);
        
        return $userId;
    }
    
    
    /**
     * Добавя линк за логване към форма, ако преди се е логвам потребител
     *
     * @param core_Form $form - форма
     *
     * @return void
     */
    public static function setLoginInfoIfNeeded(core_Form $form)
    {
        $cu = core_Users::getCurrent('id', false);
        if (isset($cu)) {
            
            return;
        }
        
        $userId = self::getUserFromSameBrid();
        if (!isset($userId)) {
            
            return;
        }
        
        // Ако потребителя не е логнат да се показва статус, подканващ към логване
        $info = new ET("<div id='editStatus'><div class='warningMsg'>[#1#] [#link#]</div></div>", tr('Ако имате регистрация, моля логнете се от|* '));
        $retUrl = array('bgerp_Portal', 'show');
        $js = 'w=window.open("' . toUrl(array('core_Users', 'login', 'ret_url' => $retUrl, 'popup' => 1)) . '","Login","width=484,height=303,resizable=no,scrollbars=no,location=0,status=no,menubar=0,resizable=0,status=0"); if(w) w.focus();';
        $loginHtml = "<a href='javascript:void(0)' oncontextmenu='{$js}' onclick='{$js}' style='text-decoration:underline;font-weight:bold'>" . tr('тук||here') . '</a>';
        $info->append($loginHtml, 'link');
        
        $form->info = new core_ET('[#1#][#2#]', $form->info, $info);
    }
    
    
    /**
     * Рефрешване на формата по AJAX ако потребителя се е логнал
     *
     * @param core_ET $tpl - шаблон на формата
     *
     * @return void
     */
    public static function setRefreshFormIfNeeded(core_ET $tpl)
    {
        $cu = core_Users::getCurrent('id', false);
        if (isset($cu)) {
            
            return;
        }
        
        core_Ajax::subscribe($tpl, array('cms_Helper', 'refreshForm'), 'refreshOrderForm', 500);
    }
    
    
    /**
     * Рефреш на формата, ако потребителя се е логнал
     */
    public function act_refreshForm()
    {
        if (Request::get('ajax_mode')) {
            $res = array();
            $cu = core_Users::getCurrent('id', false);
            
            if ($cu) {
                $obj = new stdClass();
                $obj->func = 'reload';
                
                $res[] = $obj;
            }
            
            return $res;
        }
    }
    
    
    /**
     * Проверява има ли потребител с този имейл и показва подходящо съобщение
     * 
     * @param string $email
     * 
     * @return void|string
     */
    public static function getEmailError($email)
    {
        $cu = core_Users::getCurrent('id', false);
        if (isset($cu)) return;

        // Ако има потребител с този имейл той трябва да е логнат
        if (core_Users::getUserByEmail($email)) {
            $link = ht::createLink(tr('логнете'),array('core_Users','login'));

            return "Изглежда, че има регистриран потребител с този имейл. Моля преди да продължите да се|* <b>{$link}</b>.";
        }

        // Ако имейла е вътрешен да не се допуска създаване/изпращане
        if(email_Inboxes::fetchField(array("#email = '[#1#]'", $email))){

            return "Посоченият имейл е запазен за вътрешните потребители на системата. Моля посочете друг имейл|*.";
        }
    }


    /**
     * Дали името на фирмата е разрешено
     *
     * @param string $name
     * @return string|null
     */
    public static function getErrorIfCompanyNameIsInvalid($name)
    {
        $normalizedName = str::removeWhiteSpace(mb_strtolower($name), ' ');
        $notAllowedCompanyNamesCsvData = csv_Lib::getCsvRows(getFileContent('eshop/data/NotAllowedCompanyNames.csv'), ',', '"');
        $notAllowedCompanyNamesArr = array();
        array_walk_recursive($notAllowedCompanyNamesCsvData, function ($a) use (&$notAllowedCompanyNamesArr) {
            $notAllowedCompanyNamesArr[] = $a;
        });

        if(in_array($normalizedName, $notAllowedCompanyNamesArr) || mb_strlen($normalizedName) == 1){

            return "Отговаряме само на запитвания получени от представители на реални, регистрирани в България или чужбина фирми и други юридически организации|*!";
        }

        return null;
    }


    /**
     * Коя е текущата ценова политика във външната част
     *
     * @param stdClass $settings - запис на настройките
     * @return int               - ид на ценова политика или null ако няма
     */
    public static function getCurrentEshopPriceList($settings)
    {
        $listId = $settings->listId;
        if ($lastActiveFolder = core_Mode::get('lastActiveContragentFolder')) {
            $Cover = doc_Folders::getCover($lastActiveFolder);
            $priceDateTime = null;
            if($contragentListId = price_ListToCustomers::getListForCustomer($Cover->getClassId(), $Cover->that, $priceDateTime, true)){
                $listId = $contragentListId;
            }
        }

        return $listId;
    }
}
