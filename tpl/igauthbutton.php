<?php
namespace GDO\Instagram\tpl;
use GDO\UI\GDT_Link;
/** @var $field \GDO\Instagram\GDT_IGAuthButton **/
$icon = sprintf('<img src="GDO/Instagram/img/Instagram_icon.png" title="%s" />', t('btn_continue_with_ig'));
echo GDT_Link::make()->labelNone()->href($field->href)->rawIcon($icon)->render();
