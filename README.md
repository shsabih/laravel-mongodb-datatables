# laravel-mongodb-datatables

#### Server Requirements
+ MongoDB
+ PHP >= 7.2
+ Laravel 6 or greater

### Requirements
+ Mongodb Package
https://github.com/jenssegers/laravel-mongodb
+ Laravel Repository 
https://github.com/andersao/l5-repository
### Usage

Then use the `ShexpertDatatable` facade for raw queries to the mongodb.

```php
return ShexpertDatatable::collection($this->repository, $request, true)
            ->raw('aggregate', [
                ['$unwind' => '$cities'],
                ['$match'  => [
                    'cities.isDeleted' => false,
                ]],
                ['$project' => [
                    "country" => true,
                    "cities.cityId" => true,
                    "cities.cityName" => true,
                    "cities.state" => true,
                    "cities.currency" => true,
                    "cities.currencySymbol" => true,
                    "cities.weightMetricText" => true,
                    "cities.mileageMetric" => true,
                    "cities.paymentMethods" => true,
                    "cities.isDeleted" => true,
                ]],
            ])
            ->build();
```

For selected fields and adding custom fields with them

```php
return ShexpertDatatable::collection($this->customers, $request)
                ->select([
                    'name',
                    'mobileDevices.deviceTypeMsg',
                    'email',
                    'cityId',
                    'mobileDevices.appVersion',
                    'approvedDate',
                    'registerTime',
                ])
                ->addFields([
                    'selection' => function($customer) {
                        return '<input type="checkbox" class="selection" value="'. $customer->_id .'" />';
                    },
                    'actions' => function($customer) {
                        return 'actions';
                    },
                    'credit_settings' => function($customer) {
                        return 'credit settings';
                    },
                ])
                ->build();
```
