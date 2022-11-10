<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @var $APPLICATION
 * @var $componentPath
 * @var $arResult
 */
CJSCore::Init(array("jquery"));
$assets = \Bitrix\Main\Page\Asset::getInstance();
$assets->addCss($componentPath  . "/lib/fullcalendar/fullcalendar.min.css");
$assets->addCss($componentPath  . "/lib/fullcalendar/scheduler.min.css");
$assets->addJs( $componentPath  . "/lib/fullcalendar/moment.min.js");
$assets->addJs( $componentPath  . "/lib/fullcalendar/fullcalendar.min.js");
$assets->addJs( $componentPath  . "/lib/fullcalendar/scheduler.min.js");
?>
<div id="calendar"></div>
<script>
    BX.message({
        currentDate : <?= date('Y-d-m') ?>,
        RESOURCES   : <?=\Bitrix\Main\Web\Json::encode($arResult['DATA']['RESOURCES'])?>,
        EVENTS      : <?=\Bitrix\Main\Web\Json::encode($arResult['DATA']['EVENTS'])?>,
        HOLIDAYS    : <?=\Bitrix\Main\Web\Json::encode($arResult['DATA']['HOLIDAYS'])?>,
    });
</script>