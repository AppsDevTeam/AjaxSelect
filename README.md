# ADT AjaxSelect

Disclaimer: This extension and/or its documentation is nowhere near complete. Proceed with caution.

## Installation

1. Install via composer:

    ```bash
    composer require adt/ajax-select
    ```

2. Register this extension in your config.neon:

    ```neon
    extensions:
        - ADT\Components\AjaxSelect\DI\AjaxSelectExtension
    ```
3. Include `AjaxServiceSignalTrait` in your `BasePresenter`:

    ```php
    class BasePresenter extends ... {
        use \ADT\Components\AjaxSelect\Traits\AjaxServiceSignalTrait;
    ```
4. Include `assets/ajax-select.js` to your front-end build. **TODO**
    
    ```html
    <script type="text/javascript" src="vendor/ajax-select.min.js"></script>
    ```
5. Create your first AjaxEntity.
6. Use this entity in your first AjaxSelect control.
7. Done.

## What does it do?

This extension adds following methods to `Nette\Forms\Container` and thus to all derived classes:

- `addDynamicSelect($name, $title, $items, $itemFactory = null, $config = [])`
    - dynamic select with one value
- `addDynamicMultiSelect($name, $title, $items, $itemFactory = null, $config = [])`
    - dynamic select with multiple values
- `addAjaxSelect($name, $title, $entityName = $name, $config = [])`
    - ajax select with one value
- `addAjaxMultiSelect($name, $title, $entityName = $name, $config = [])`
    - ajax select with multiple values
   
### Config

```php
[
	AjaxSelectExtension::CONFIG_INVALID_VALUE_MODE => AjaxSelectExtension::INVALID_VALUE_MODE_*,
]
```

### Dynamic Select

This control allows passing unknown value to `$control->value` field. Doing so will invoke control's `$itemFactory` with only one parameter - the invalid value.

The item factory can either return title for given value or empty value (`NULL`, empty string, zero etc.). Non-empty value is automatically appended to known list of valid values.

### Ajax Select

This control needs something we call AjaxEntity, and its factory. All user AjaxEntities need to derive from our `AbstractEntity` or `AggregateEntity`.

This AjaxEntity encapsulates `$itemFactory`'s behaviour but it can get much more powerful.

## Configuration

### Implement AjaxEntity

First, create new class (ie. `UserAjaxEntity`) that derives from our `AbstractEntity`. In addition, We will need its factory, so create an interface (ie. `IUserAjaxEntityFactory`) too. Example:

```php

namespace App\Model\Ajax;

interface IUserAjaxEntityFactory {
    /** @return UserAjaxEntity */
    function create();
}

class UserAjaxEntity extends \ADT\Components\AjaxSelect\Entities\AbstractEntity {

    const OPTION_ACTIVE = 'active';
    
    public function active($bool) {
        // if $bool is TRUE, only active users are shown,
        // otherwise, only inactive are shown
        return $this->set(self::OPTION_ACTIVE, $bool);
    }
    
    public function findValues($limit) {
        $active = $this->get(self::OPTION_ACTIVE);
        
        // TODO return user ids depending on $active
    }
    
    public function formatValues($value) {
        // TODO return array of userId => userName
    }
    
    public function isValidValue($value) {
        $active = $this->get(self::OPTION_ACTIVE);
        
        // TODO check if passed ids are of active/inactive users,
        // depending on $active
    }

}
```

Then register this entity and its factory in your `config.neon` in `services` section:

```neon
services:
    -
        create: \App\Model\Ajax\UserAjaxEntity
        implement: \App\Model\Ajax\IUserAjaxEntityFactory
        tags: [ajax-select.entity-factory]
```

This instructs Nette to autoimplement a factory for your entity and tag it as `ajax-select.entity-factory`. AjaxSelect knows about your entity now.

Now you can use your AjaxEntity direcly from your AjaxSelect control on your Nette form:

```php
$form->addAjaxSelect('user', 'Please select active user')
    ->getAjaxEntity()
        ->active(TRUE)
    ->back()
    ->setRequired(TRUE);

$form->addAjaxSelect('inactiveUser', 'Please select inactive user', 'user')
    ->getAjaxEntity()
        ->active(FALSE);
```

You can omit third argument of `addAjaxSelect` or `addAjaxMultiSelect` if it's equal to control name (ie. first argument).

Calling `getAjaxEntity()` on AjaxSelect returns instance of your AjaxEntity.
Calling `back()` on AjaxEntity returns its parent. In this case, control itself is returned.

AjaxEntity name, its options and query URL are serialized to control's `data-ajax-select` HTML attribute.

### Change signal name

If you ever need to change signal that is used in query URL, proceed as follows:

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