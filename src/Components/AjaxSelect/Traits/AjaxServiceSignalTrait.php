<?php

namespace ADT\Components\AjaxSelect\Traits;

use \ADT\Components\AjaxSelect;

/**
 * @method void sendResponse($response)
 */
trait AjaxServiceSignalTrait {

	/** @var AjaxSelect\Services\AjaxService @inject */
	public $ajaxSelectService;

	public function handleGetAjaxItems($entityName, array $entityOptions) {
		$entity = $this->ajaxSelectService->createEntity($entityName);
		$entity->parseOptions($entityOptions);

		$values = $entity->findValues(30);
		$response = $entity->formatJsonValues($values);

		$this->sendResponse(new \Nette\Application\Responses\JsonResponse($response));
	}

}