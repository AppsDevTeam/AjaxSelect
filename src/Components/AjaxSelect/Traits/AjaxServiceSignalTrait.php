<?php

namespace ADT\Components\AjaxSelect\Traits;

use \ADT\Components\AjaxSelect;

/**
 * @method void sendResponse($response)
 */
trait AjaxServiceSignalTrait {

	/** @var AjaxSelect\Services\AjaxService @inject */
	protected $ajaxService;

	public function handleGetAjaxItems($entityName, array $entityOptions) {
		$entity = $this->ajaxService->createEntity($entityName);
		$entity->parseOptions($entityOptions);

		$values = $entity->findValues(30);
		$response = $entity->formatJsonValues($values);

		$this->sendResponse(new \Nette\Application\Responses\JsonResponse($response));
	}

}