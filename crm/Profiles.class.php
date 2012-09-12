<?php
/**
 * Мениджър на потребителски профили
 *
 * @category  bgerp
 * @package   crm
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class crm_Profiles extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = array();


    /**
     * Заглавие на мениджъра
     */
    var $title = "Профили";


    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Профил";


    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/vcard.png';


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'plg_Created,crm_Wrapper,plg_RowTools';


    /**
     * Кой  може да пише?
     */
    var $canWrite = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('userId', 'key(mvc=core_Users, select=nick, allowEmpty)', 'caption=Потребител');
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent,caption=Визитка');
        
        $this->setDbUnique('userId,personId');
    }
    
    
    /**
     * Екшън за смяна на парола
     * 
     * return core_ET
     */
    public static function act_ChangePassword()
    {
        $data = new stdClass();
        
        $data->form = static::getForm();
        
        /* @var $form core_Form */
        $form = &$data->form;

        foreach($form->fields as &$field) {
            $field->input = 'none';
        } 
        
        $form->FNC('old', 'password', 'caption=Стара парола,input,mandatory');
        $form->FNC('new', 'password', 'caption=Нова Парола,input,mandatory');
        $form->FNC('newAgain', 'password', 'caption=Нова Парола (пак),input,mandatory');
        
        $retUrl = getRetUrl();
        
        // Ако формата е успешно изпратена - запис, лог, редирект
        if (static::validatePasswordForm($form)) {
            // Записваме данните
            if (core_Users::setPassword($form->rec->new))  {
                // Правим запис в лога
                static::log('change_password');
            
                // Редиректваме към предварително установения адрес
                return new Redirect($retUrl, 'Паролата е сменена успешно');
            }
        }
        
        // Подготвяме лентата с инструменти на формата
        $form->toolbar->addSbBtn('Смяна', 'change_password', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        // Потготвяме заглавието на формата
        $form->title = 'Смяна на паролата';
        
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        // Опаковаме изгледа
        $tpl = static::renderWrapping($tpl, $data);
        
        return $tpl;
    }
    
    
    /**
     * Зарежда от заявката и валидира полетата на формата за смяна на парола
     *  
     * @param core_Form $form
     * @return boolean
     */
    protected static function validatePasswordForm(core_Form $form)
    { 
        $form->input();
        
        if ($form->isSubmitted()) {
            if (core_Users::getCurrent('ps5Enc') != core_Users::encodePwd($form->rec->old)) {
                $form->setError('old', 'Грешна стара парола');
            }
            
            if ($form->rec->new != $form->rec->newAgain) {
                $form->setError('new', 'Двете пароли не съвпадат');
                $form->setError('newAgain');
            }
        }
    
        return $form->isSubmitted();
    }
    

    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $usersQuery = core_Users::getQuery();

        $opt = array('' => '&nbsp;');

        $query = self::getQuery();

        $used = array();

        while($rec = $query->fetch()) {
            if($rec->id != $data->form->rec->id) {
                $used[$rec->userId] = TRUE;
            }
        }
 
        while($uRec = $usersQuery->fetch("#state = 'active'")) {
            if(!$used[$uRec->id]) {
                $opt[$uRec->id] = $uRec->nick;
            }
        }

        $data->form->setOptions('userId', $opt);
    }
    

    /*
     * Методи за подготовка и показване на потребителски профил като детайл на визитка
     * 
     *  o prepareProfile()
     *  o renderProfile()
     *  
     */
    
    /**
     * Подготвя данните необходими за рендиране на профил на визитка
     */
    function prepareProfile($data)
    {
        expect($data->masterId);

        $data->profile = static::fetch("#personId = {$data->masterId}");

        if($data->profile->userId) {
            if ($data->profile) {
                $data->profile->userRec = core_Users::fetch($data->profile->userId);
                if(core_Users::getCurrent() == $data->profile->userId) {
                    $data->profile->userRec->lastLoginTime = core_Users::getCurrent('lastLoginTime');
                    $data->profile->userRec->lastLoginIp = core_Users::getCurrent('lastLoginIp');
                }
            }
        
            if($data->profile->userRec->id == core_Users::getCurrent('id') || haveRole('admin')) {
                $data->changePassUrl =  array($this, 'changePassword', 'ret_url'=>TRUE);
            }
        }

        $data->canChange = haveRole('admin');
    }
    
    
    /**
     * Рендира потребителски профил
     */
    function renderProfile($data)
    {
        $tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        
        $tpl->append(tr('Потребителски профил'), 'title');
        
        if ($data->profile->userId) {
            $profileTpl = new ET(getFileContent('crm/tpl/Profile.shtml'));
            $userRow = core_Users::recToVerbal($data->profile->userRec);
            
            $profileTpl->append(str_repeat('*', 7), 'password');

            if ($data->changePassUrl) {
                $changePasswordBtn = ht::createLink(
                    '(' . tr('cмяна' . ')'), $data->changePassUrl, FALSE, 
                    'title=' . tr('Смяна на парола')
                );
                $profileTpl->append($changePasswordBtn, 'password');
            }

            $profileTpl->placeObject($userRow);

            $tpl->append($profileTpl, 'content');
        } 
 
        if($data->canChange && !Mode::is('printing')) {
            if(!$data->profile->userId) {
                $tpl->append('<p>' . tr("Няма профил") . '</p>', 'content');
            }
            if(!$data->profile) {
                $url = array($this, 'edit', 'personId' => $data->masterId, 'ret_url' => TRUE);
                $img = "<img src=" . sbf('img/16/add.png') . " width='16' height='16'>";
                $tpl->append(
                    ht::createLink(
                        $img, $url, FALSE, 
                        'title=' . tr('Асоцииране с потребител')
                    ), 
                    'title'
                );
            } else {
                $url = array($this, 'edit', $data->profile->id, 'ret_url' => TRUE);
                $img = "<img src=" . sbf('img/16/edit.png') . " width='16' height='16'>";
                $tpl->append(
                    ht::createLink(
                        $img, $url, FALSE, 
                        'title=' . tr('Смяна на потребител')
                    ), 
                    'title'
                );
            }
        }
        
        return $tpl;
    }

    
    /**
     * След инсталиране на пакета CRM:
     * 
     *  о Създаване на CRM-група за потребителски профили (ако няма)
     *  o Конфигуриране на групата за профили с екстендер 'profile'
     *
     * @param crm_Profiles $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $profilesGroup = 'Потребителски профили';  // @TODO да се изнесе като клас-променлива или в конфиг.
        
        core_Users::forceSystemUser();
        
        // Създаване (ако няма) на група в crm_Groups за потребителските профили. 
        if (!$profiles = crm_Groups::fetch("#name = '{$profilesGroup}'")) {
            $profiles = (object)array(
                'name' => $profilesGroup,
                'extenders' => 'profile',
            );
            crm_Groups::save($profiles);
        }
        
        // Добавяме (ако няма) екстендер 'profiles' на групата за потребителски профили 
        $extenders = type_Keylist::toArray($profiles->extenders);
        
        if (!isset($extenders['profile'])) {
            $profiles->extenders['profile'] = 'profile';
            $profiles->extenders = type_Keylist::fromArray($profiles->extenders);
            crm_Groups::save($profiles);
        }
        
        core_Users::cancelSystemUser();
    }
    
    
    /**
     * Промяна на данните на асоциирания потребител след запис на визитка
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param core_Master $master
     */
    public static function on_AfterMasterSave(core_Mvc $mvc, $rec, core_Master $master)
    {
        if (get_class($master) != 'crm_Persons') {
            return;
            expect(get_class($master) != 'crm_Person'); // дали не е по-добре така?
        }
        
        static::updateUser($rec);
    }
    
    
    /**
     * Извилича служебната CRM-група в която се записват потребителските профили
     * 
     * @return stdClass
     */
    public static function fetchCrmGroup()
    {
        $profilesGroup = 'Потребителски профили'; // @TODO да се изнесе като клас-променлива или в конфиг.
        
        return crm_Groups::fetch("#name = '{$profilesGroup}'");
    }
    
    
    /**
     * Визитката, асоциирана с потребителски акаунт
     * 
     * @param int $userId
     * @return stdClass
     */
    public static function getProfile($userId = NULL)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent('id');
        }
        
        if (!$profile = static::fetch("#userId = {$userId}")) {
            return NULL;
        }
        
        return crm_Persons::fetch($profile->personId);
    }
    
    
    /**
     * Създаване на потребителски профил за потребител
     * 
     *  o Създава визитка на потребителя с частен достъп
     *  о Добавя визитката в системната CRM-група за профили
     *  o Свързва (чрез crm_Profiles) новата визитка с потребителя
     * 
     * @param stdClass $userRec
     * @return boolean
     */
    public static function createProfile($userRec)
    {   
        // Ако липсват данните за профил - нищо не правим
        if(!$userRec->names || !$userRec->email) {
            return;
        }

        expect($profilesGroup = static::fetchCrmGroup());
        
        $personRec = (object)array(
            'name' => $userRec->names,
            'email' => $userRec->email,
            'groupList' => type_Keylist::fromArray(array($profilesGroup->id=>$profilesGroup->id)),
            'access' => 'private',
            'inCharge' => $userRec->id,
            '_skipUserUpdate' => TRUE, // Флаг за предотвратяване на безкраен цикъл след 
                                       // създаване на потребител!
        );
        
        if (!crm_Persons::save($personRec)) {
            return FALSE;
        }
            
        $profileRec = (object)array(
            'userId'   => $userRec->id,
            'personId' => $personRec->id,
        );
        
        return static::save($profileRec) !== FALSE;
    }
    
    /**
     * Синхронизиране на данните (имена, имейл) на профилната визитка с тези на потребител
     * 
     * @param stdClass $userRec
     * @return boolean
     */
    public static function updatePerson($userRec)
    {
        if ($userRec->_skipPersonUpdate) {
            // След запис на визитка се обновяват данните (имена, имейл) на асоциирания с нея 
            // потребител. Ако сме стигнали до тук по този път, не обновяваме отново данните
            // на визитката след промяна на потребителя, защото това води до безкраен цикъл!
            return;
        }
        
        $profileRec = static::fetch("#userId = {$userRec->id}");
        
        if (!$profileRec) {
            // Нищо не правим, ако потребителя няма профил
            return;
        }
        
        $personRec = crm_Persons::fetch($profileRec->personId);

        // Ако профила сочи към несъществуваща визитка, нищо не правим
        // Може би трябва да изтрием профиля?
        if(!$personRec) {
            return;
        }

        if(!$userRec->names || !$userRec->email) {
            return;
        }

        
        $personRec->email = type_Emails::prepend($personRec->email, $userRec->email);
        $personRec->name  = $userRec->names;
        
        // Флаг за предотвратяване на безкрайния цикъл: промяна на визитка -> потребител -> 
        // визитка -> ...
        $personRec->_skipUserUpdate  = TRUE;
        
        return crm_Persons::save($personRec);
    }
    
    
    /**
     * Обновяване на данните на асоциирания потребител след промяна във визитка
     * 
     * @param stdClass $personRec
     */
    public static function updateUser($personRec)
    {
        if ($personRec->_skipUserUpdate) {
            return;
        }
        
        $profile = static::fetch("#personId = {$personRec->id}");
        
        if (!$profile) {
            return;
        }
        
        // Обновяване на записа на потребителя след промяна на асоциираната му визитка
        $userRec = core_Users::fetch($profile->userId);
        if(!$userRec) {
            return;
        }
        
        if (!empty($personRec->email)) {
            // Вземаме първия (валиден!) от списъка с лични имейли на лицето
            $emails = type_Emails::toArray($personRec->email);
            if (!empty($emails)) {
                $userRec->email = reset($emails);
            }
        }
        if (!empty($personRec->name)) {
            $userRec->names = $personRec->name;
        }
        
        // Флаг за предотвратяване на безкраен цикъл след промяна на визитка
        $userRec->_skipPersonUpdate = TRUE;
        
        core_Users::save($userRec);
        
        // Обновяване на граватара на потребителя след промяна на асоциираната му визитка
        
        // @TODO ...
    }
}