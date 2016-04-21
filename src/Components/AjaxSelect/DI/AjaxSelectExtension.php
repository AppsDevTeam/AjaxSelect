<?php

namespace ADT\Components\AjaxSelect\DI;

class AjaxSelectExtension extends \Nette\DI\CompilerExtension {

	public function afterCompile(\Nette\PhpGenerator\ClassType $class) {
		$builder = $this->getContainerBuilder();

		/* TODO: zaregistrovat AjaxService
		$builder->addDefinition($this->prefix('articles'))
			->setClass('MyBlog\ArticlesModel', array('@connection'));
		*/

		$initialize = $class->getMethod('initialize');
		$initialize->addBody(__CLASS__ . '::register($this);');
	}

	public static function register(\Nette\DI\Container $container) {
		$entityFactories = $container->findByTag('adt.ajax-select.entity-factory');
		
		
	}

}