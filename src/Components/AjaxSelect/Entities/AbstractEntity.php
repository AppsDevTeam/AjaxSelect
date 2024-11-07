<?php

namespace ADT\Components\AjaxSelect\Entities;

use ADT\Components\AjaxSelect;
use Nette\Application\UI\Presenter;
use Nette\SmartObject;

abstract class AbstractEntity {

	use SmartObject;

	const DATA_ATTRIBUTE_NAME = 'data-adt-ajax-select';

	const OPTION_QUERY = 'q';

	/** @var string */
	protected $name;

	/** @var array */
	protected $config;

	/** @var bool */
	protected $isDirty = TRUE;

	/** @var array */
	protected $options = [ ];

	/** @var AjaxSelect\Traits\AjaxServiceControlTrait|NULL */
	protected $control = NULL;

	/**
	 * @param array $options
	 * @return $this
	 */
	public function setOptions(array $options) {
		$this->options = $options;
		$this->isDirty = TRUE;
		return $this;
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	public function parseOptions(array $options) {
		array_walk_recursive($options, function (&$value) {
			if ($value === 'true') {
				$value = TRUE;
			} else if ($value === 'false') {
				$value = FALSE;
			}
		});
		return $this->setOptions($options);
	}

	/**
	 * @internal
	 * @param array $values
	 * @return bool[] $value => $isValid
	 */
	public function areValidValues(array $values) {
		// all values are invalid by default
		$result = array_combine($values, array_fill(0, count($values), FALSE));

		$control = $this->getControl();
		$form = $control->getForm();

		$query = $this->createQueryObject()
			->byId($values);

		$this->filterQueryObject($query);

		AjaxSelect\Traits\OrByIdFilterTrait::applyOrByIdFilter($this->config, $form, $control->getName(), $query);

		foreach ($query->fetch() as $row) {
			// pouze selectnuté jsou validní
			$result[$row->getId()] = TRUE;
		}

		return $result;
	}

	/**
	 * @internal
	 * @param mixed $value
	 * @return bool
	 */
	public function isValidValue($value) {
		$areValid = $this->areValidValues([ $value ]);
		return !empty($areValid[$value]);
	}

	/**
	 * @internal
	 * @param int $limit
	 * @return array List of ids.
	 */
	public function findValues($limit) {
		$query = $this->createQueryObject();
		$this->filterQueryObject($query);

		$rows = $query
			->fetch()
			->applyPaging(0, $limit)
			->toArray();

		return array_map(function ($row) {
			return $row->getId();
		}, $rows);
	}

	/**
	 * @internal
	 * @param array $values
	 * @return array List of items.
	 */
	public abstract function formatValues($values);

	/**
	 * @internal
	 * @param array $values
	 * @return array List of items.
	 */
	public function formatJsonValues($values) {
		$result = [ ];
		$titles = $this->formatValues($values);
		$count = count($titles);
		$i = 1;
		foreach ($titles as $id => $item) {
			if (!is_array($item)) {
				$item = [
					'text' => $item,
				];
			}
			$item['id'] = $id;
			$item['aria-posinset'] = $i;
			$item['aria-setsize'] = $count;
			$result[] = $item;
			$i++;
		}
		return $result;
	}

	/**
	 * @return \ADT\BaseQuery\BaseQuery freshly created QO without filters
	 */
	protected abstract function createQueryObject();

	/**
	 * @param \ADT\BaseQuery\BaseQuery $query
	 */
	protected abstract function filterQueryObject($query);

	/**
	 * @param string $option
	 * @param mixed $value
	 * @return $this
	 */
	public function set($option, $value) {
		$this->options[$option] = $value;
		$this->isDirty = TRUE;
		return $this;
	}

	/**
	 * @param string $option
	 * @param mixed|NULL $defaultValue
	 * @return mixed|NULL
	 */
	public function get($option, $defaultValue = NULL) {
		return isset($this->options[$option])
			? $this->options[$option]
			: $defaultValue;
	}

	/**
	 * @internal
	 * @return AjaxSelect\Interfaces\IAjaxServiceControl|NULL
	 */
	public function getControl() {
		return $this->control;
	}

	/**
	 * @internal
	 * @param AjaxSelect\Interfaces\IAjaxServiceControl $control
	 * @return $this
	 */
	public function setControl(AjaxSelect\Interfaces\IAjaxServiceControl $control) {
		$this->control = $control;
		return $this;
	}

	/**
	 * Serializes ajax data to HTML attribute.
	 * @return $this
	 */
	public function done() {
		$control = $this->getControl();

		if (/*$this->isDirty && */$control) { // TODO: set dirty = TRUE on value change
			$control->monitor(Presenter::class, function ($presenter) use ($control) {
				$getItemsSignal = $this->config[AjaxSelect\DI\AjaxSelectExtension::CONFIG_GET_ITEMS_SIGNAL_NAME];
				$controlValue = $control->getValue();

				$data = [
					'url' => $presenter->link($getItemsSignal . '!', ['htmlId' => $control->getHtmlId()]),
					'initialItems' => $controlValue
						? $this->formatJsonValues(
							is_array($controlValue)
								? $controlValue
								: [ $controlValue ]
						)
						: [],
					'queryParam' => static::OPTION_QUERY,
					'entityName' => $this->getName(),
					'entityOptions' => $this->getOptions(),
				];

				if ($control instanceof AjaxSelect\Interfaces\IPromptControl) {
					$data['prompt'] = $control->translate($control->getPrompt());
				}

				if ($control instanceof AjaxSelect\Interfaces\IMultiSelectControl) {
					$data['multiple'] = TRUE;
				}

				$control->setAttribute(static::DATA_ATTRIBUTE_NAME, $data);
				$this->isDirty = FALSE;
			});
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * @param array $config
	 * @return $this
	 */
	public function setConfig(array $config) {
		$this->config = $config;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

}
