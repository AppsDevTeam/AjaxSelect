<?php

namespace ADT\Components\AjaxSelect\Interfaces;

use ADT\Components\AjaxSelect\Entities\AbstractEntity;
use Doctrine\ORM\AbstractQuery;
use Kdyby\Doctrine\ResultSet;
use Kdyby\Persistence\Queryable;

interface IQueryObject {

	/**
	 * Required only for ajaxSelect
	 * @param int|int[]|AbstractEntity|AbstractEntity[] $ids
	 * @return static
	 */
	public function byIds($ids);

	/**
	 * To include default value of entity into select set
	 * @param int|int[] $id
	 * @return static
	 */
	public function orById($id);

	/**
	 * Required only for dynamicSelect to set the key and value to complete passed QO
	 * @return static
	 */
	public function selectPairs();

	/**
	 * Checks if selectPairs function must be called or if selectPairs is already defined
	 * @return bool
	 */
	public function callSelectPairsAuto();

	/**
	 * @param Queryable $repository
	 * @param int $hydrationMode
	 * @return array|ResultSet|mixed|object|\stdClass|null
	 */
	public function fetch(Queryable $repository, $hydrationMode = AbstractQuery::HYDRATE_OBJECT);

}
