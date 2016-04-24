# ADT AjaxSelect

## Installation

1. Install via composer:
```bash
composer require adt/ajax-select
```
2. Done.

## Configuration

### Change signal name

1. edit your `config.neon`
```neon
	ajaxSelect:
		getItemsSignalName: yourSignalName
```
2. rename trait method
    Rewrite `use AjaxServiceSignalTrait;` as follows
    ```php
    use AjaxServiceSignalTrait {
        handleGetAjaxItems as handleYourSignalName;
    }
    ```