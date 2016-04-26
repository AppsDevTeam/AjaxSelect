<?php

namespace ADT\Components\AjaxSelect;


class AjaxSelect extends \Nette\Forms\Controls\SelectBox implements Interfaces\IAjaxServiceControl, Interfaces\IPromptControl {

	use Traits\AjaxServiceControlTrait;
	
	use Traits\InvalidValueTrait;

}