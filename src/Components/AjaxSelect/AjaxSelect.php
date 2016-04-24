<?php

namespace ADT\Components\AjaxSelect;


class AjaxSelect extends \Nette\Forms\Controls\SelectBox implements Interfaces\IAjaxServiceControl {

	use Traits\AjaxServiceControlTrait;
	
	use Traits\InvalidValueTrait;

}