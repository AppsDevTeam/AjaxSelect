<?php

namespace ADT\Components\AjaxSelect\Entities;

use ADT\Components\AjaxSelect;
use ADT\DoctrineComponents\QueryObject\QueryObject;
use Nette\Application\UI\Presenter;
use Nette\SmartObject;

abstract class AbstractEntity
{
	use SmartObject;

	const DATA_ATTRIBUTE_NAME = 'data-adt-ajax-select';
	const OPTION_QUERY = 'q';

	/** Stands in for the HTML id in a cached items URL, see getItemsUrl(). */
	private const HTML_ID_PLACEHOLDER = 'adtAjaxSelectHtmlId';

	/** @var \WeakMap<Presenter, array<string, string>>|null items URL per presenter and signal */
	private static ?\WeakMap $itemsUrls = null;

	public abstract function formatValues($values): array;
	public abstract function hydrateValues($values, array $formValues = []): array;

	protected abstract function createQueryObject(): QueryObject;

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

		if (method_exists($this, 'filterQueryObject')) {
			$this->filterQueryObject($query);
		}

		if ($query instanceof AjaxSelect\Interfaces\OrdByIdFilterInterface) {
			$query->applyOrByIdFilter($this->config, $form, $control->getName());
		}

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

		return $query->fetch($limit);
	}

	/**
	 * @internal
	 * @param array $values
	 * @return array List of items.
	 */
	public function formatJsonValues($values, array $formValues = []) {
		$result = [ ];
		$titles = $this->formatValues($this->hydrateValues($values, $formValues));
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
					'url' => static::getItemsUrl($presenter, $getItemsSignal, $control->getHtmlId()),
					'initialItems' => $controlValue
						? $this->formatJsonValues(
							is_array($controlValue)
								? $controlValue
								: [ $controlValue ],
							$this->getFormValues($control->getForm())
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
	 * Form values passed to hydrateValues() when the value of a select is set or rendered.
	 *
	 * By default all values of the form. Collecting them walks every control of the form, and
	 * it happens for every select whose value is set - a form with hundreds of selects (rows of
	 * a document) then becomes quadratically slow. An entity whose hydrateValues() needs only
	 * a few fields, or none, should override this and return just those.
	 */
	public function getFormValues(\Nette\Forms\Form $form): array {
		return $form->getValidatedValues('array');
	}

	/**
	 * URL the select loads its items from.
	 *
	 * The URL of every select on a page differs only in the htmlId parameter. Building it
	 * with Presenter::link() for each select means walking the whole component tree of the
	 * presenter every time (getGlobalState()), which makes a form with hundreds of selects
	 * quadratically slow. The URL is therefore built once per presenter and signal and only
	 * the htmlId is substituted.
	 */
	protected static function getItemsUrl(Presenter $presenter, string $signal, string $htmlId): string {
		self::$itemsUrls ??= new \WeakMap();
		$urls = self::$itemsUrls[$presenter] ?? [];

		if (!isset($urls[$signal])) {
			$urls[$signal] = $presenter->link($signal . '!', ['htmlId' => self::HTML_ID_PLACEHOLDER]);
			self::$itemsUrls[$presenter] = $urls;
		}

		return str_replace(self::HTML_ID_PLACEHOLDER, urlencode($htmlId), $urls[$signal]);
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
