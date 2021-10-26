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
    AjaxSelectExtension::CONFIG_OR_BY_ID_FILTER => TRUE,
]
```

### Dynamic Select

This control allows passing unknown value to `$control->value` field. Doing so will invoke control's `$itemFactory` with only one parameter - the invalid value.

The item factory can either return title for given value or empty value (`NULL`, empty string, zero etc.). Non-empty value is automatically appended to known list of valid values.

DynamicSelect accepts array or `\Kdyby\Doctrine\QueryObject` that extends `\ADT\BaseQuery\BaseQuery` in `$items`.

Following features are implemented if QO is passed:
- Function `\ADT\BaseQuery\BaseQuery::callSelectPairsAuto()` defines if `\ADT\BaseQuery\BaseQuery::selectPairs()` function should be called automatically. `selectPairs` sets entity attributes as select key and value. 
  - Default values in `\ADT\BaseQuery\BaseQuery` are `SELECT_PAIRS_KEY` = 'id' and `SELECT_PAIRS_VALUE` = null, which returns whole object, so you should override the value constant for your needs, for example to `name`. When calling `selectPairs` function or overriding the constant, you can also use entity getter name which returns more complex value. For example `nameWithEmail` which then calls function `getNameWithEmail` from entity object.
  - Function `callSelectPairsAuto` should be expanded when custom fetch function in QO is defined, and we continue to process fetched data from default call of fetch function in `\ADT\BaseQuery\BaseQuery`. See the function in the DynamicSelect example below.
- Entity in `\Kdyby\DoctrineForms\EntityForm` can have inactive default value set, which causes error of not allowed in selected items. Therefore, the automatic call of `\ADT\BaseQuery\BaseQuery::orById()` function is called, which sets this inactive value to items.
  - `AjaxSelectExtension::CONFIG_OR_BY_ID_FILTER` can turn off this default call. 
  - This function must be turned off for attributes that are not mapped by processed entity. For example if we are in `UserForm`. Entity `User` has properties `id`, `name` and `role`. In `UserForm` we can create dynamicSelect with orByIdFilter turned on for `role` property. But if we create dynamicSelect for custom item like `profession`, orByIdFilter must be turned off, because it's not mapped property of `User` entity.

### Ajax Select

This control needs something we call AjaxEntity, and its factory. All user AjaxEntities need to derive from our `AbstractEntity` or `AggregateEntity`.
 - if `AbstractEntity` is used, you must implement following functions:
   - `createQueryObject` which returns created query object of specific entity that inherits from `\ADT\BaseQuery\BaseQuery`.
   - `filterQueryObject`: in this function you call all filter functions from your QO. 
   - `formatValues`: here you get the filtered data from your QO to format them to the desired form.

This AjaxEntity encapsulates `$itemFactory`'s behaviour but it can get much more powerful.

AjaxSelect also uses orByIdFilter, see `Dynamic Select`.

## Configuration

### Implement AjaxEntity

First, create new class (ie. `UserAjaxEntity`) that derives from our `\ADT\Components\AjaxSelect\Entities\AbstractEntity`. 

`\ADT\Components\AjaxSelect\Entities\AbstractEntity` requires few functions to be implemented. See example below.

In addition, we will need its factory, so create an interface (ie. `IUserAjaxEntityFactory`) too. 

Example:

```php

namespace App\Model\Ajax;

interface IUserAjaxEntityFactory {
    /** @return UserAjaxEntity */
    function create();
}

class UserAjaxEntity extends \ADT\Components\AjaxSelect\Entities\AbstractEntity {

    const OPTION_OR_BY_ID = 'orById';
    const OPTION_BY_ID = 'byId';
    const OPTION_ACTIVE = 'active';
    
    /** @var \Kdyby\Doctrine\EntityManager */
    protected $em;
	
    /** @var \App\Queries\IUserFactory This object is defined below in DynamicSelect implementation */
    protected $userQueryFactory;

    public function __construct(\Kdyby\Doctrine\EntityManager $em, \App\Queries\IUserFactory $userQueryFactory) {
        $this->em = $em;
        $this->userQueryFactory = $userQueryFactory;
    }
    
    /**
     * @param int|int[] $id
     * @return $this
     */
    public function orById($id) {
        return $this->set(static::OPTION_OR_BY_ID, $id);
    }

    /**
     * @param int|int[] $id
     * @return $this
     */
    public function byId($id) {
        return $this->set(static::OPTION_BY_ID, $id);
    }
    
    public function active($bool) {
        // if $bool is TRUE, only active users are shown,
        // otherwise, only inactive are shown
        return $this->set(self::OPTION_ACTIVE, $bool);
    }
    
    /**
     * This function is required by \ADT\Components\AjaxSelect\Entities\AbstractEntity
     * @param array $values 
     * @return array
     */
    public function formatValues($value) {
        // TODO return array of userId => userName
    }
    
    /**
     * This function is required by \ADT\Components\AjaxSelect\Entities\AbstractEntity
     * @internal
     * @return Queries\User
     */
    protected function createQueryObject()
    {
        return $this->userQueryFactory->create();
    }

    /**
     * This function is required by \ADT\Components\AjaxSelect\Entities\AbstractEntity
     * @internal
     * @param Queries\User $query
     */
    protected function filterQueryObject(&$query) {
        if ($value = $this->get(static::OPTION_OR_BY_ID)) {
            $query->orById($value);
        }
        
        if ($value = $this->get(static::OPTION_BY_ID)) {
            $query->byId($value);
        }
        
        if ($value = $this->get(static::OPTION_ACTIVE)) {
            $query->byActive($value);
        }
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
$form->addAjaxSelect('user', 'Active users with default user', function (UserAjaxEntity $ajaxEntity) {
    $ajaxEntity
        ->active(TRUE);
})
    ->setRequired(TRUE);

$form->addAjaxSelect('inactiveUser', 'Inactive users without default user', 'user', function (UserAjaxEntity $ajaxEntity) {
    $ajaxEntity
        ->active(FALSE);
}, [
    AjaxSelectExtension::CONFIG_OR_BY_ID_FILTER => FALSE
]);
```

Arguments `$entityName` and/or `$entitySetupCallback` can be omitted. You can omit `$entityName` if it's equal to control name (ie. first argument `$name`).

Finally you have to call finalizing ajaxSelect after the form is attached to presenter.
For example you can do it in your `BaseForm::attached($presenter)`

```php
/** @var \ADT\Components\AjaxSelect\Services\EntityPoolService $ajaxEntityPoolService */
$ajaxEntityPoolService->invokeDone();
```

AjaxEntity name, its options and query URL are serialized to control's `data-ajax-select` HTML attribute.

### Implement DynamicSelect with QueryObject

First, create new class (ie. `User`) that derives from `\ADT\BaseQuery\BaseQuery`.

In addition, We will need its factory, so create an interface (ie. `IUserFactory`) too. 

Example:

```php

namespace App\Queries;

interface IUserFactory {
    /** @return User */
    function create();
}

class User extends \ADT\BaseQuery\BaseQuery {

    const OPTION_ACTIVE = 'active';
    
    protected $fetchWithDataEmail = FALSE;
    
    public function active() {
        $this->filter[static::OPTION_ACTIVE] = function (\Kdyby\Doctrine\QueryBuilder $qb) {
            $qb->andWhere('e.active = TRUE');
        };
        return $this;
    }
	
    public function disableActiveFilter() {
        unset($this->filter[static::OPTION_ACTIVE]);
        return $this;
    }
    
    protected function doCreateQuery(\Kdyby\Persistence\Queryable $repository) {
        $qb = parent::doCreateQuery($repository);
        $qb->addOrderBy('e.name');

        return $qb;
    }
    
    /**
     * @param Queryable|null $repository
     * @param int $hydrationMode
     * @return array|\Kdyby\Doctrine\ResultSet|mixed|object|\stdClass|null
     */
    public function fetch(?Queryable $repository = null, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        $fetch = parent::fetch($repository, $hydrationMode);

        if ($this->fetchWithDataEmail) {
            $array = [];
            foreach ($fetch as $person) {
                $array[$person->getId()] = \Nette\Utils\Html::el('option')
                    ->setAttribute('value', $person->getId())
                    ->setHtml($person->getName())
                    ->setAttribute('data-email', $person->getEmail());
            }

            $fetch = $array;
        }

        return $fetch;
    }

    /**
     * @return $this
     */
    public function fetchOptionsWithEmail()
    {
        $this->fetchWithDataEmail = TRUE;

        return $this;
    }

    /**
     * @return bool
     */
    public function callSelectPairsAuto()
    {
        return ! $this->fetchWithDataEmail && parent::callSelectPairsAuto();
    }

}
```

Now you can create DynamicSelect on your Nette form:

```php
// Active users with default user
$entityForm->addDynamicSelect('user', 'Active users', $this->userQueryFactory->create()->active())
    ->setRequired(TRUE);

// All users without default user
$entityForm->addDynamicSelect('allUser', 'All users', $this->userQueryFactory->create(), NULL, [
    \ADT\Components\AjaxSelect\DI\AjaxSelectExtension::CONFIG_OR_BY_ID_FILTER => FALSE
]);

// Active users with email in label
$entityForm->addDynamicSelect('userWithEmail', 'All users', $this->userQueryFactory->create()->selectPairs('nameWithEmail'));

// Attribute that is not mapped so CONFIG_OR_BY_ID_FILTER must be turned off
$entityForm->addDynamicSelect('profession', 'Profession', $this->userQueryFactory->create(), NULL, [
    \ADT\Components\AjaxSelect\DI\AjaxSelectExtension::CONFIG_OR_BY_ID_FILTER => FALSE
]);
```

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

## ToDo

### orById filtr pro zanořené selecty

Pokud máme select uvnitř toMany nebo addDynamic, tak musí být nastaveno `AjaxSelectExtension::CONFIG_OR_BY_ID_FILTER => FALSE`, aby se knihovna nepokoušela přistoupit k atributu dle názvu selectu v hlavní entitě, což by skončilo chybou. Toto rozšíření orById filtru pro zanořené selecty by se dalo naimplementovat, že bychom si v kontejneru selectu zjistili, kam je select zanořený (může být víc než jedna úroveň zanoření) a podle toho bychom místo `$form->getEntity()->get{$atributeName}()` provolali všechny prvky zanoření, tedy `$form->getEntity()->get{$zanořenýPrvek}($indexPrvku)->...->get{$atributeName}()`
