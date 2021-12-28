**ActivityPubValidateActivityStreamsTwoData**: To extend an Activity properties that we are managing from JSON
* `@param  string  $type_name`    When we handle a Type, we will send you the type identifier of the one being handleded
* `@param  array   &$validators`  attribute => Validator the array key should have the attribute name that you want to hand, the value should be a validator class
* `@return`                       Returns `Event::next`

Example:

```php
public function onActivityPubValidateActivityStreamsTwoData(string $type_name, array &$validators): bool {
    if ($type_name === '{Type}') {
        $validators['attribute'] = myValidator::class;
    }
    return Event::next;
}
```

The Validator should be of the form:

```php
class myValidator extends \Plugin\ActivityPub\Util\ModelValidator
{
    /**
     * Validate Attribute's value
     *
     * @param mixed $value from JSON's attribute
     * @param mixed $container A {Type}
     * @return bool
     * @throws Exception
     */
    public function validate($value, $container): bool
    {
        // Validate that container is a {Type}
        \ActivityPhp\Type\Util::subclassOf($container, \ActivityPhp\Type\Extended\Object\{Type}::class, true);

        return {Validation Result};
```

**ActivityPubAddActivityStreamsTwoData**: To add attributes to an entity that we are managing to JSON (commonly federating out via ActivityPub)
* `@param  string                            $type_name`       When we handle a Type, we will send you the type identifier of the one being handleded
* `@param  \ActivityPhp\Type\AbstractObject  &$type_activity`  The Activity in the intermediate format between Model and JSON
* `@return`                                                    Returns `Event::next`

**ActivityPubActivityStreamsTwoResponse**: To add a route to ActivityPub (the route must already exist in your plugin) (commonly being requested to ActivityPub)
* `@param  string                                 $route`      Route identifier
* `@param  array                                  $vars`       From your controller
* `@param  \Plugin\ActivityPub\Util\TypeResponse  &$response`  The JSON (protip: ModelResponse's handler will convert entities into TypeResponse)
* `@return`                                                    Returns `Event::stop` if handled, `Event::next` otherwise

Example:

```php
public function onActivityPubActivityStreamsTwoResponse(string $route, arrray $vars, ?TypeResponse &$response = null): bool {
        if ($route === '{Object route}') {
                $response = \Plugin\ActivityPub\Util\ModelResponse::handle($vars[{Object}]);
                return Event::stop;
        }
        return Event::next;
}
```

**NewActivityPubActivity**: To convert an Activity Streams 2.0 formatted activity into Entities (commonly when we receive a JSON in our inbox)
* `@param  Actor                                            $actor`          Actor who authored the activity
* `@param  \ActivityPhp\Type\AbstractObject                 $type_activity`  Activity
* `@param  \ActivityPhp\Type\AbstractObject                 $type_object`    Object
* `@param  ?\Plugin\ActivityPub\Entity\ActivitypubActivity  &$ap_act`        ActivitypubActivity
* `@return`                                                                  Returns `Event::stop` if handled, `Event::next` otherwise

**NewActivityPubActivityWithObject**: To convert an Activity Streams 2.0 formatted activity with a known object into Entities (commonly when we receive a JSON in our inbox)
* `@param  Actor                                            $actor`          Actor who authored the activity
* `@param  \ActivityPhp\Type\AbstractObject                 $type_activity`  Activity
* `@param  Entity                                           $type_object`    Object
* `@param  ?\Plugin\ActivityPub\Entity\ActivitypubActivity  &$ap_act`        ActivitypubActivity
* `@return`                                                                  Returns `Event::stop` if handled, `Event::next` otherwise

**GSVerbToActivityStreamsTwoActivityType**: Translate a GNU social internal verb to an Activity Streams 2.0 one
* `@param  string                                           $verb`                                  GNU social's internal verb
* `@param  \ActivityPhp\Type\AbstractObject                 &$gs_verb_to_activity_stream_two_verb`  Activity Streams 2.0 verb
* `@return`                                                                                         Returns `Event::stop` if handled, `Event::next` otherwise

Example:

```php
public function onGSVerbToActivityStreamsTwoActivityType(string $verb, ?string &$gs_verb_to_activity_stream_two_verb): bool
{
    if ($verb === '{GS verb}') {
        $gs_verb_to_activity_stream_two_verb = '{AS2 verb}';
        return Event::stop;
    }
    return Event::next;
}
```

