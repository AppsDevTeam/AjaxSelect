<?php

namespace ADT\Components\AjaxSelect\Interfaces;

interface OrdByIdFilterInterface 
{
	public function orById($id): static;
	public function applyOrByIdFilter(array $config, \Nette\Forms\Container $form, string $attributeName);
}