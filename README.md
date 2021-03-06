# ADT AjaxSelect

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
4. Include `assets/ajax-select.js` to your front-end build.
    
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
    - `$itemFactory`: `function (array $invalidValues, DynamicSelect $input)`
- `addDynamicMultiSelect($name, $title, $items, $itemFactory = null, $config = [])`
    - dynamic select with multiple values
    - `$itemFactory`: see `addDynamicSelect`
- `addAjaxSelect($name, $title, $entityName = $name, $entitySetupCallback = NULL, $config = [])`
    - ajax select with one value
- `addAjaxMultiSelect($name, $title, $entityName = $name, $entitySetupCallback = NULL, $config = [])`
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
$form->addAjaxSelect('user', 'Please select active user', function (UserAjaxEntity $ajaxEntity) {
    $ajaxEntity
        ->active(TRUE);
})
    ->setRequired(TRUE);

$form->addAjaxSelect('inactiveUser', 'Please select inactive user', 'user', function (UserAjaxEntity $ajaxEntity) {
    $ajaxEntity
        ->active(FALSE);
});
```

Arguments `$entityName` and/or `$entitySetupCallback` can be omitted. You can omit `$entityName` if it's equal to control name (ie. first argument `$name`).

Finally you have to call finalizing ajaxSelect after the form is attached to presenter.
For example you can do it in your `BaseForm::attached($presenter)`

```php
/** @var \ADT\Components\AjaxSelect\Services\EntityPoolService $ajaxEntityPoolService */
$ajaxEntityPoolService->invokeDone();
```

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
   
## Troubleshooting

### Dynamic form containers (like addDynamic and toMany)

If you create a new form container which contains an input with AjaxEntity and you create it after calling `$ajaxEntityPoolService->invokeDone();` (which is called typically after form initialization), then the ajax search will not work properly.

Example of such mistake:

```php
<?php // Form.php

public function init($form) {

    $form->addDynamic('address', function ($container) {
        $container->addAjaxSelect('city', 'City', function ($ajaxEntity) {
            $ajaxEntity
                ->byCountryCode('CZ');
        });
    });
    
    // No container exists right now
}

```
```latte
{* Form.latte *}

<div n:foreach="[0, 1, 2] as $addressIndex">
    {* When you access $form['address'][$addressIndex], then the container, "city" input and its AjaxEntity are created *}
    {label $form['address'][$addressIndex]['city'] /} {input $form['address'][$addressIndex]['city']}
</div>
```

Right solution:


```php
<?php // Form.php

public function init($form) {

    $form->addDynamic('address', function ($container) {
        $container->addAjaxSelect('city', 'City', function ($ajaxEntity) {
            $ajaxEntity
                ->byCountryCode('CZ');
        });
    });

    // This will create 3 new containers, its "city" input and AjaxEntity
    $form->setDefaults([
        'address' => [
            0 => [],
            1 => [],
            2 => [],
        ],
    ]);
}
```
```latte
{* Form.latte *}

{* We render only those containers, which already exists. *}
<div n:foreach="$form['address']->getContainers() as $container">
    {label $container['city'] /} {input $container['city']}
</div>
```

