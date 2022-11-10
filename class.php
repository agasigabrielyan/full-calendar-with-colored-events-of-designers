<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Class finds and outputs data into FullCalendar
 *
 * DevConsult, https://dev-consult.ru
 * info@dev-consult.ru
 */
use Bitrix\Crm\Entity\Deal;
use Bitrix\Main\Engine\Action;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;


class Calendar extends \CBitrixComponent {
    CONST LOW_QUALITY_LEADS = [
        'JUNK',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
        '10',
        '11',
        '12',
    ];
    CONST DEPARTMENT_ID = 59;
    private $resources = [];
    private $events = [];
    private $eventsUrls = [];


    /**
     * method returns events
     *
     * @return array
     */
    private function getData() {
        // получим лиды со встречами, но у которых нет сделок
        $leads = $this->getLeads();

        // получим сделки со встречами
        $deals = $this->getDeals();

        // соберем $this->resources - массив менеджеров из $leads и $deals
        $this->makeManagersResourcesArray($leads);
        $this->makeManagersResourcesArray($deals);

        // соберем массив событий $this->events
        $this->getEntities($deals);
        $this->getEntities($leads);

        $this->getEventsWithoutDuplicateDealsLeads();
        $this->getAbsenceDaysOfWorkersAsEvents();

        $this->sortResources();

        $data = [];
        $data['RESOURCES'] = $this->resources;
        $data['EVENTS'] = $this->events;

        return $data;
    }

    /**
     * Метод собирает сделки со встречами
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getDeals() {
        $deals = \Bitrix\Crm\DealTable::getList([
            'select' => [
                'BIND_ACTIVITY_ID' => 'bind.ACTIVITY_ID',

                'DEAL_ID' => 'ID',
                'DEAL_TITLE' => 'TITLE',
                'DEAL_LEAD_ID' => 'LEAD_ID',

                'ACTIVITY_ID' => 'activity.ID',
                'ACTIVITY_PROVIDER_TYPE_ID' => 'activity.PROVIDER_TYPE_ID',
                'ACTIVITY_TITLE' => 'activity.SUBJECT',
                'ACTIVITY_START_TIME' => 'activity.START_TIME',
                'ACTIVITY_END_TIME' => 'activity.END_TIME',
                'ACTIVITY_OWNER_ID' => 'activity.OWNER_ID',
                'ACTIVITY_RESPONSIBLE_ID' => 'activity.RESPONSIBLE_ID',

                'USER_ID' => 'user.ID',
                'USER_NAME' => 'user.NAME',
                'USER_LAST_NAME' => 'user.LAST_NAME',
                'USER_DEPARTMENT' => "user.UF_DEPARTMENT",

                'LEAD_STATUS_ID' => 'lead.STATUS_ID',

                'STATUS_NAME' => 'status.NAME'
            ],
            'filter' => [
                'ACTIVITY_PROVIDER_TYPE_ID' => 'MEETING',
            ],
            'order' => [
                'ID' => 'DESC'
            ],
            'runtime' => [
                'bind' => [
                    'data_type' => \Bitrix\Crm\ActivityBindingTable::getEntity(),
                    'reference' => [
                        '=this.ID' => 'ref.OWNER_ID'
                    ]
                ],
                'lead' => [
                    'data_type' => \Bitrix\Crm\LeadTable::getEntity(),
                    'reference' => [
                        '=this.DEAL_LEAD_ID' => 'ref.ID'
                    ]
                ],
                'activity' => [
                    'data_type' => \Bitrix\Crm\ActivityTable::getEntity(),
                    'reference' => [
                        '=this.BIND_ACTIVITY_ID' => 'ref.ID'
                    ]
                ],
                'user' => [
                    'data_type' => \Bitrix\Main\UserTable::getEntity(),
                    'reference' => [
                        '=this.ACTIVITY_RESPONSIBLE_ID' => 'ref.ID'
                    ]
                ],
                'status' => [
                    'data_type' => \Bitrix\Crm\StatusTable::getEntity(),
                    'reference' => [
                        '=this.LEAD_STATUS_ID' => 'ref.STATUS_ID'
                    ]
                ]
            ]
        ])->fetchAll();

        // удалим из списка сделок, те которые созданы не дизайнерами
        foreach( $deals as $dealKey => $dealValue ) {
            $deals[$dealKey]['TARGET_ENTITY'] = "THIS_IS_DEAL";
            if( !in_array(self::DEPARTMENT_ID, $dealValue['USER_DEPARTMENT']) ) {
                unset($deals[$dealKey]);
            }
        }

        return $deals;
    }

    /**
     * Метод собирает лиды со встречами и статусами лидов
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getLeads() {
        $leads = \Bitrix\Crm\LeadTable::getList([
            'select' => [
                'BIND_ACTIVITY_ID' => 'bind.ACTIVITY_ID',

                'LEAD_ID' => 'ID',
                'LEAD_TITLE' => 'TITLE',
                'LEAD_STATUS_ID' => 'STATUS_ID',
                'LEAD_ASSIGNED_BY_ID' => 'ASSIGNED_BY_ID',

                'DEAL_ID' => 'deal.ID',

                'ACTIVITY_ID' => 'activity.ID',
                'ACTIVITY_PROVIDER_TYPE_ID' => 'activity.PROVIDER_TYPE_ID',
                'ACTIVITY_TITLE' => 'activity.SUBJECT',
                'ACTIVITY_START_TIME' => 'activity.START_TIME',
                'ACTIVITY_END_TIME' => 'activity.END_TIME',
                'ACTIVITY_OWNER_ID' => 'activity.OWNER_ID',
                'ACTIVITY_RESPONSIBLE_ID' => 'activity.RESPONSIBLE_ID',

                'USER_ID' => 'user.ID',
                'USER_NAME' => 'user.NAME',
                'USER_LAST_NAME' => 'user.LAST_NAME',
                'USER_DEPARTMENT' => 'user.UF_DEPARTMENT',

                'STATUS_STATUS_ID' => 'status.STATUS_ID',
                'STATUS_ENTITY_ID' => 'status.ENTITY_ID',
                'STATUS_NAME' => 'status.NAME'
            ],
            'order' => [
                'LEAD_ID' => 'DESC'
            ],
            'filter' => [
                'ACTIVITY_PROVIDER_TYPE_ID' => 'MEETING',
                'DEAL_ID' => null,
                'STATUS_ENTITY_ID' => 'STATUS'
            ],
            'runtime' => [
                'bind' => [
                    'data_type' => \Bitrix\Crm\ActivityBindingTable::getEntity(),
                    'reference' => [
                        '=this.LEAD_ID' => 'ref.OWNER_ID'
                    ]
                ],
                'activity' => [
                    'data_type' => \Bitrix\Crm\ActivityTable::getEntity(),
                    'reference' => [
                        '=this.BIND_ACTIVITY_ID' => 'ref.ID'
                    ]
                ],
                'deal' => [
                    'data_type' => \Bitrix\Crm\DealTable::getEntity(),
                    'reference' => [
                        '=this.ID' => 'ref.LEAD_ID'
                    ]
                ],
                'user' => [
                    'data_type' => \Bitrix\Main\UserTable::getEntity(),
                    'reference' => [
                        '=this.ACTIVITY_RESPONSIBLE_ID' => 'ref.ID'
                    ]
                ],
                'status' => [
                    'data_type' => \Bitrix\Crm\StatusTable::getEntity(),
                    'reference' => [
                        '=this.LEAD_STATUS_ID' => 'ref.STATUS_ID'
                    ]
                ]
            ]
        ])->fetchAll();

        // удалим из списка лидов, те которые созданы не дизайнерами
        foreach( $leads as $leadKey => $leadValue ) {
            $leads[$leadKey]['TARGET_ENTITY'] = 'THIS_IS_LEAD';
            if( !in_array(self::DEPARTMENT_ID, $leadValue['USER_DEPARTMENT']) ) {
                unset($leads[$leadKey]);
            }
        }

        return $leads;
    }

    /**
     *  Метод создает массив событий, которые планируется вывести на календарь
     *
     * @param array $arrayOfCrmEntities
     */
    private function getEntities(array $arrayOfCrmEntities) {
        global $USER;
        $currentUserId = $USER->GetID();
        $designers = \Bitrix\Main\UserTable::getList([
            'select' => ['ID'],
            'filter' => [
                'UF_DEPARTMENT' => [self::DEPARTMENT_ID]
            ]
        ])->fetchAll();

        $designers = array_column($designers, 'ID');

        $events = [];

        // создаем массив со всеми встречами, которые внесем в массив $events
        foreach ($arrayOfCrmEntities as $key => $crmEntity) {

            // если текущий пользователь не является дизайнером то накладываем
            if( (in_array($currentUserId, $designers )) ) {
                if( ($currentUserId != $crmEntity['ACTIVITY_RESPONSIBLE_ID']) ) {
                   continue;
                }
            }

            $start = $this->fDate($crmEntity['ACTIVITY_START_TIME']);
            $end = $this->fDate($crmEntity['ACTIVITY_END_TIME']);

            $id = 'event-' . $crmEntity['ACTIVITY_TITLE'] . '-' .$crmEntity['ACTIVITY_RESPONSIBLE_ID'];
            $resourceId = $crmEntity['ACTIVITY_RESPONSIBLE_ID'];
            $title = $crmEntity['ACTIVITY_TITLE'];
            $className = "calendar-cell calendar-cell__real-event";

            if( $crmEntity['TARGET_ENTITY'] === "THIS_IS_DEAL" ) {
                $url = "/crm/deal/details/".$crmEntity['DEAL_ID']."/";
                $color = "#468EE5";
            } else {
                $url = "/crm/lead/details/".$crmEntity['LEAD_ID']."/";
                if( in_array( $crmEntity['LEAD_STATUS_ID'], self::LOW_QUALITY_LEADS) ) {
                    $color = "#fff";
                    $className = "calendar-cell calendar-cell__real-event calendar-cell__low-quality-lead";
                } else {
                    $color = "#00A64C";
                }
            }
            $leadStatusName = $crmEntity['STATUS_NAME'];

            $extendedProps = [
                'id'                => $id,
                'resourceId'        => $resourceId,
                'title'             => $title,
                'url'               => $url,
                'className'         => $className,
                'leadStatusName'    => $leadStatusName,
                'start'             => $start,
                'end'               => $end
            ];

            $events[] = [
                "id" => $id,
                "resourceId" => $resourceId,
                "start" => $start,
                "end"=> $end,
                "title"=> $title,
                "url"=> $url,
                "color" => $color,
                "className" => $className,
                "extendedProps"=> $extendedProps,
            ];

            $this->eventsUrls[] = $url;

        }

        $this->events = array_merge( $this->events, $events );
    }

    /**
     * Метод удаляет ссылки на лиды, которые дублируют ссылки на сделки, созданные на базе этих лидов
     */
    private function getEventsWithoutDuplicateDealsLeads() {
        $events = $this->events;
        $eventsUrls = $this->eventsUrls;

        $newEventsArray = [];
        foreach ($events as $eventKey => $eventValue) {
            $url = $eventValue['url'];
            $isLeadUrl = strpos($url, "/lead/");
            if( $isLeadUrl ) {
                $fakeDealUrl = preg_replace("~/lead/~", "/deal/", $url);
            }

            if( !( in_array($fakeDealUrl, $eventsUrls) ) ) {
                $newEventsArray[] = $eventValue;
            }

        }

        $this->events  = [];
        $this->events  = $newEventsArray;
    }

    private function getAbsenceDaysOfWorkersAsEvents() {
        $dbAbsences = \Bitrix\Iblock\Elements\ElementAbsenceTable::getList([
            'select' => [
                "*",
                "USER_PROPERTY_" => "USER",
                "FINISH_STATE_PROPERTY_" => "FINISH_STATE",
                "STATE_PROPERTY_" => "STATE",
                "ABSENCE_TYPE_PROPERTY_" => "ABSENCE_TYPE",
            ],
        ]);

        $absences = [];
        while($abs = $dbAbsences->Fetch()) {
            $absences[] = $abs;
        }

        $absencesEvents = [];
        foreach($absences as $singleAbsence) {
            $start = $this->fDate($singleAbsence['ACTIVE_FROM']);
            $end = $this->fDate($singleAbsence['ACTIVE_TO']);

            $title = "Отсутствует: ";
            $title .= $singleAbsence['NAME'] . " ";


            $absencesEvents[] = [
                "id"=>"absence" . $singleAbsence['ID'].$singleAbsence['USER_PROPERTY_VALUE'],
                "resourceId"=>$singleAbsence['USER_PROPERTY_VALUE'],
                "start"=>$start,
                "end"=>$end,
                "title"=> $title,
                "rendering" => 'background',
                "color" => "red",
                "className" => "calendar-cell calendar-cell__absence-day",
                "extendedProps"=>[
                    "className" => "calendar-cell__absence-day"
                ]
            ];
        }

        $this->events = array_merge($this->events, $absencesEvents);

    }

    /**
     * Метод собирает массив уникальных менеджеров-дизайнеров
     *
     * @param array $arrayOfCrmEntities
     */
    private function makeManagersResourcesArray(array $arrayOfCrmEntities) {
        global $USER;
        $currentUserId = $USER->GetID();
        $designers = \Bitrix\Main\UserTable::getList([
            'select' => ['ID'],
            'filter' => [
                'UF_DEPARTMENT' => [self::DEPARTMENT_ID]
            ]
        ])->fetchAll();

        $designers = array_column($designers, 'ID');

        foreach ( $arrayOfCrmEntities as $key => $value ) {
            // если текущий пользователь не является дизайнером то накладываем
            if( (in_array($currentUserId, $designers )) ) {
                if( ($currentUserId != $value['ACTIVITY_RESPONSIBLE_ID']) ) {
                    continue;
                }
            }

            $newUser = [
                'id' => $value['USER_ID'],
                'title' => $value['USER_NAME'] . " " . $value['USER_LAST_NAME']
            ];
            if( !in_array( $newUser, $this->resources ) ) {
                $this->resources[] = $newUser;
            }
        }
    }

    /**
     * method return array of dates of holidays
     *
     * @param $startDate
     * @param $endDate
     * @param $holidaysOfWeek array массив дней недели выходных пользователя
     * @return array
     */
    private function getArrayOfHolidaysOfPeriod( $startDate, $endDate, $holidaysOfWeek ) {
        $arrayOfHolidays = [];

            for( $date = $startDate; $date <= $endDate; $date->modify('+1 day') ){
                $weekDayNumber = intval( date( "w", strtotime($date->format('Y-m-d')) ) );
                $realHolidays = array_values($holidaysOfWeek);

                if($weekDayNumber === 0) {
                    $realDayNumber = 7;
                } else {
                    $realDayNumber = $weekDayNumber;
                }

                if( in_array($realDayNumber, $realHolidays) ) {
                    $arrayOfHolidays[] = $date->format('Y-m-d');
                }
            }

        return $arrayOfHolidays;
    }

    /**
     * method returns users and their worksdays
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getUserWithWorksDays() {

        CModule::IncludeMOdule('timeman');

        $scheduleList = ScheduleTable::getList([
            'select' => ['ID']
        ])->fetchAll();

        $scheduleList = array_column($scheduleList, 'ID');

        $allWorktimeData = [];
        foreach ($scheduleList as $scheduleId) {
            $scheduleRepository = DependencyManager::getInstance()->getScheduleRepository();
            $schedule = $scheduleRepository->findByIdWith($scheduleId, [
                'SHIFTS',  'USER_ASSIGNMENTS'
            ]);

            $provider = DependencyManager::getInstance()->getScheduleProvider();
            $users = $provider->findActiveScheduleUserIds($schedule);
            $scheduleForm = new ScheduleForm($schedule);

            $shiftTemplate = new \Bitrix\Timeman\Form\Schedule\ShiftForm();
            $shiftFormWorkDays = [];
            foreach (array_merge([$shiftTemplate], $scheduleForm->getShiftForms()) as $shiftIndex => $shiftForm)
            {
                $shiftFormWorkDays[] = array_map('intval', str_split($shiftForm->workDays));
            }

            $worktime = [];
            foreach ($users as $userId)
            {
                foreach($shiftFormWorkDays as $key => $value) {
                    if( $value[0] !== 0 ) {
                        $worktime[$userId] = $value;
                    }
                }
            }

            $allWorktimeData[] = $worktime;
        }

        return $allWorktimeData;
    }

    /**
     * method modifies date format to put into FullCalendar format
     *
     * @param $dateObj
     * @return string
     */
    private function fDate($dateObj) {
        $date = date('Y-m-d',($dateObj->getTimestamp()));
        $time = date('H:i:s',($dateObj->getTimestamp()));
        $result = $date . "T" . $time;
        return $result;
    }

    private function sortResources() {
        sort($this->resources);
    }

    public function executeComponent()
    {
        global $APPLICATION;

        $this->arResult['DATA'] = $this->getData();
        $this->includeComponentTemplate();
        $APPLICATION->SetTitle('Календарь событий');
    }
}