<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use \Bitrix\Main\Localization\Loc;

$arComponentDescription = array(
    "NAME" => Loc::getMessage('CALENDAR_COMPONENT_NAME'),
    "DESCRIPTION" => Loc::getMessage('CALENDAR_COMPONENT_DESCRIPTION'),
    "SORT" => "500",
    "PATH" => array(
        "ID" => "devconsult",
        "NAME" => Loc::getMessage("DEVCONSULT_COMPANY_NAME")
    )
);