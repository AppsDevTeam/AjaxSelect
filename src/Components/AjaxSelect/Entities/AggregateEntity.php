<?php

namespace ADT\Components\AjaxSelect\Entities;

abstract class AggregateEntity extends AbstractEntity {

	/** @var AbstractEntity[] */
	protected $entities = [ ];

	/** @var string */
	protected $prefixSeparator;

	/**
	 * @param AbstractEntity[] $entities
	 * @param string $prefixSeparator
	 */
	public function __construct(array $entities, $prefixSeparator = ':') {
		$this->entities = $entities;
		$this->prefixSeparator = $prefixSeparator;

		// make back() return this entity
		foreach ($this->entities as $entity) {
			$entity->setBackValue($this);
		}
	}

	/**
	 * @return array
	 */
	public function getOptions() {
		$result = $this->options;

		foreach ($this->entities as $prefix => $entity) {
			$result[$prefix] = $entity->getOptions();
		}

		return $result;
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	public function setOptions(array $options) {
		parent::setOptions($options);

		foreach ($this->entities as $prefix => $entity) {
			if (isset($options[$prefix])) {
				$entity->setOptions($options[$prefix]);

				// pass query parameter to nested entities
				if (($value = $this->get(static::OPTION_QUERY)) !== NULL) {
					$entity->set(static::OPTION_QUERY, $value);
				}
			}
		}

		return $this;
	}


	/**
	 *
	 * @param $prefix
	 * @return mixed
	 */
	protected abstract function getGroupTitle($prefix);

	/**
	 * Groups values by known prefixes.
	 * @param $value
	 * @return array|bool
	 */
	protected function groupByPrefix($value) {
		if (!is_array($value)) {
			$value = [ $value ];
		}

		// prepare array with prefixes
		$prefixes = array_keys($this->entities);
		$prefixes = array_combine(
			$prefixes, // key is original prefix
			array_map(function ($prefix) {
				return $prefix . $this->prefixSeparator;
			}, $prefixes) // value is prefix with separator
		);

		// sort prefixes by length, desc
		uasort($prefixes, function ($a, $b) {
			$a = mb_strlen($a);
			$b = mb_strlen($b);

			return $a - $b;
		});

		$byPrefix = [ ];
		foreach ($value as $item) {
			foreach ($prefixes as $prefix => $prefixWithSeparator) {
				if (mb_strpos($item, $prefix) === 0) {
					$itemWithoutPrefix = \Nette\Utils\Strings::after($item, $prefixWithSeparator);
					$byPrefix[$prefix][] = $itemWithoutPrefix;
					continue 2;
				}
			}

			// value has invalid prefix or no prefix at all -> it's invalid
			return FALSE;
		}

		return $byPrefix;
	}

	/**
	 * @param mixed $value
	 * @return string[]
	 */
	public function parsePrefixAndValue($value) {
		$byPrefix = $this->groupByPrefix($value);
		$prefix = array_keys($byPrefix)[0];
		$value = $byPrefix[$prefix][0];

		return [
			'prefix' => $prefix,
			'value' => $value
		];
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function parsePrefix($value) {
		return $this->parsePrefixAndValue($value)['prefix'];
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	public function parseValue($value) {
		return $this->parsePrefixAndValue($value)['value'];
	}

	/**
	 * @param string $prefix
	 * @param mixed $value
	 * @return string
	 */
	public function prefix($prefix, $value) {
		return $prefix . $this->prefixSeparator . $value;
	}

	/**
	 * @internal
	 * @param mixed|array $value
	 * @return bool
	 */
	public function isValidValue($value) {
		$byPrefix = $this->groupByPrefix($value);

		if ($byPrefix === FALSE) {
			return FALSE;
		}

		// check if all nested entities report their values as valid
		foreach ($byPrefix as $prefix => $values) {
			$entity = $this->entities[$prefix];

			if (!$entity->isValidValue($values)) {
				// if at least one does not, value is invalid
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * @internal
	 * @param int $limit
	 * @return array List of ids.
	 */
	public function findValues($limit) {
		$result = [ ];

		// adjust limit
		$limit = $limit / count($this->entities);

		foreach ($this->entities as $prefix => $entity) {
			foreach ($entity->findValues($limit) as $id) {
				$result[$prefix][] = $this->prefix($prefix, $id);
			}
		}

		return $result;
	}

	/**
	 * @internal
	 * @param array $values
	 * @return array List of items.
	 */
	public function formatValues($values) {
		$byPrefix = $this->groupByPrefix($values);

		$result = [ ];
		foreach ($byPrefix as $prefix => $values) {
			$values = $this->entities[$prefix]->formatValues($values);

			foreach ($values as $id => $text) {
				$result[$this->prefix($prefix, $id)] = $text;
			}
		}

		return $result;
	}

	/**
	 * @internal
	 * @param array $values
	 * @return array List of items.
	 */
	public function formatJsonValues($values) {
		if (array_diff_key($values, $this->entities)) {
			$values = $this->groupByPrefix($values);
		}

		$result = [ ];
		foreach ($values as $prefix => $group) {
			// use nested entity for formatting
			$group = $this->groupByPrefix($group)[$prefix];
			$children = $this->entities[$prefix]->formatJsonValues($group);

			// prefix ids
			$children = array_map(function ($row) use ($prefix) {
				$row['id'] = $this->prefix($prefix, $row['id']);
				return $row;
			}, $children);

			$result[] = [
				'text' => $this->getGroupTitle($prefix),
				'children' => $children,
			];
		}

		return $result;
	}
}