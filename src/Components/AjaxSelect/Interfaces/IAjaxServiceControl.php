<?php

namespace ADT\Components\AjaxSelect\Interfaces;


use ADT\Components\AjaxSelect;


/**
 * @method \Nette\ComponentModel\IComponent lookup($name, $need = TRUE)
 * @method string getHtmlId()
 * @method $this setAttribute($name, $value)
 */
interface IAjaxServiceControl extends \Nette\Forms\IControl {

	/**
	 * @return string
	 */
	public function getAjaxEntityName();

	/**
	 * @return AjaxSelect\Entities\AbstractEntity
	 */
	public function getAjaxEntity();

	/**
	 * @param AjaxSelect\Entities\AbstractEntity $entity
	 * @return $this
	 */
	public function setAjaxEntity(AjaxSelect\Entities\AbstractEntity $entity);
}