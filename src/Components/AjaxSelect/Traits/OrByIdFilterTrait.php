<?php

namespace ADT\Components\AjaxSelect\Traits;

use ADT\Components\AjaxSelect;

trait OrByIdFilterTrait {

	/**
	 * @param array $config
	 * @param \Nette\Forms\Container $form
	 * @param string $attributeName
	 * @param \ADT\BaseQuery\BaseQuery $query
	 */
	public static function applyOrByIdFilter(array $config, \Nette\Forms\Container $form, string $attributeName, \ADT\BaseQuery\BaseQuery $query)
	{
		// if orByIdFilter is active and it is entity form, the value, which is set in entity->inputName is included in items.
		if ($config[AjaxSelect\DI\AjaxSelectExtension::CONFIG_OR_BY_ID_FILTER] && method_exists($form, 'getEntity') && !empty($form->getEntity())) {
			$defaultValue = $form->getEntity()->{'get' . ucfirst($attributeName)}();

			//It can be Kdyby\Doctrine\Collections\Readonly\ReadOnlyCollectionWrapper
			if ($defaultValue && method_exists($defaultValue, 'toArray')) {
				$defaultValue = $defaultValue->toArray();
			}

			if (!empty($defaultValue)) {
				$query->orById($defaultValue);
			}
		}
	}

}
