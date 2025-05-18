<?php

namespace App\Traits;

use App\Jobs\ExportDataJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Symfony\Component\HttpFoundation\Response;
use Exception;

trait BaseModelTrait
{
    // define filterable attributes
    protected $bmt_filterable = [];

    // encryptable columns in the model
    protected $bmt_encryptable = [];

    // seeding data
    protected $bmt_seeding = [];

    // data validation
    protected $bmt_validation = [];

    // log data
    protected $bmt_log = [];

    // cache
    protected $bmt_caching = [];
    // export
    protected $bmt_export = [];

    // Define default templates (if any)
    protected $bmt_templates = [];


    // Boot method to attach model events
    protected static function bootBaseModelTrait()
    {
        static::creating(function ($model) {
            $model->logMessage('info', 'Creating: ' . get_class($model), $model->toArray());
        });

        static::updating(function ($model) {
            $model->logMessage('info', 'Updating: ' . get_class($model), $model->toArray());
        });

        static::deleting(function ($model) {
            $model->logMessage('info', 'Deleting: ' . get_class($model), $model->toArray());
        });

        if (method_exists(self::class, 'restoring')) {
            static::restoring(function ($model) {
                $model->logMessage('info', 'Restoring: ' . get_class($model), $model->toArray());
            });
        }

        if (method_exists(self::class, 'forceDeleting')) {
            static::forceDeleting(function ($model) {
                $model->logMessage('info', 'Force Deleting: ' . get_class($model), $model->toArray());
            });
        }

        static::saving(function ($model) {
            // check if the model has auto encryption
            if ($model->isAutoEncrypted()) {
                $model->encryptAttributes();
            }
        });

        static::retrieved(function ($model) {
            $model->decryptAttributes();
        });
    }

    // ****************************************************************************
    // ********************************** HELPERS *********************************
    // ****************************************************************************
    // ****************************************************************************
    public function getClassName()
    {
        return get_class($this);
    }

    /**
     * get static properties for the application
     * 
     * @param string $name
     * @return mixed
     */
    public static function getAppProperties($name)
    {
        return config("application.properties.{$name}");
    }

    // ****************************************************************************
    // ****************************** DATA ENCRYPTION *****************************
    // ****************************************************************************
    // ****************************************************************************
    /**
     * Retrieve the list of encryptable columns from the configuration file
     * 
     * for usign the configuration file (config/encryption.php) use this format as example
     */
    // return [
    //     'models' => [
    //         \App\Models\User::class => [
    //             'encryptable' => ['email', 'address'],
    //         ],
    //         \App\Models\Order::class => [
    //             'encryptable' => ['credit_card_number'],
    //         ],
    //     ],
    // ];
    protected function getEncryptableColumnsConfig()
    {
        if (empty($this->bmt_encryptable)) {
            $this->bmt_encryptable = Config::get("application.models.{$this->getClassName()}.encryptable", []);
        }

        return $this->bmt_encryptable;
    }

    /**
     * retrun if the model has auto encryption
     * 
     * @return bool auto_encrypt
     */
    protected function isAutoEncrypted()
    {
        return Config::get("application.models.{$this->getClassName()}.auto_encryted") ?? false;
    }

    /**
     * Encrypt specified attributes
     * @return void
     */
    public function encryptAttributes()
    {
        $encryptable = $this->getEncryptableColumnsConfig();

        foreach ($encryptable as $key) {
            if (!empty($this->attributes[$key]) && !$this->isEncrypted($this->attributes[$key])) {
                $this->attributes[$key] = Crypt::encryptString($this->attributes[$key]);
            }
        }
    }


    /**
     * Decrypt specified attributes
     * @return void
     */
    public function decryptAttributes()
    {
        $encryptable = $this->getEncryptableColumnsConfig();

        foreach ($encryptable as $key) {
            if (!empty($this->attributes[$key]) && $this->isEncrypted($this->attributes[$key])) {
                $this->attributes[$key] = Crypt::decryptString($this->attributes[$key]);
            }
        }
    }

    /**
     * Check if a value is encrypted
     * 
     * @param string value
     * @return bool is_encrypted
     */
    public function isEncrypted($value)
    {
        try {
            // try to decrypt this bitch
            Crypt::decryptString($value);

            return true; // this bitch is encrypted
        } catch (Exception) {
            return false; // sight..., we dodged a bullet
        }
    }



    // ****************************************************************************
    // ************************** ADD/UPDATE/DELETE DATA **************************
    // ****************************************************************************
    // ****************************************************************************
    /**
     * Add a new record, with validation
     *
     * @param array values
     * @return Model|array
     */
    public static function addRecord(array $values)
    {
        $instance = new static;

        // extract only the needed keys from the array of values
        $values = array_intersect_key($values, array_flip($instance->fillable));

        // Validate the request
        $instance->validateAttributes($values);

        // Create the new record
        try {
            $instance->fill($values);
            $instance->save();

            // $instance->logMessage('info', 'Created: ' . get_class($instance), $instance->toArray());
        } catch (Exception $e) {
            $instance->logMessage('error', 'Error creating: ' . get_class($instance), ['error' => $e->getMessage()]);
            return ['errors' => 'There was an error creating the record.'];
        }

        return $instance;
    }

    /**
     * Update an existing record, with validation
     *
     * @param array values
     * @param int $id
     * @return Model|array
     */
    public function updateRecord(array $values)
    {
        // extract only the needed keys from the array of values
        $values = array_intersect_key($values, array_flip($this->fillable));

        // Validate the request
        $this->validateAttributes($values);

        // Update the record
        try {
            $this->fill($values);
            $this->save();

            // $this->logMessage('info', 'Updated: ' . get_class($this), $this->toArray());
        } catch (Exception $e) {
            $this->logMessage('error', 'Error updating: ' . get_class($this), ['error' => $e->getMessage()]);
            return ['errors' => 'There was an error updating the record.'];
        }

        return $this;
    }

    /**
     * Delete a record
     *
     * @param int $id
     * @return bool|array
     */
    public function deleteRecord()
    {
        try {
            $result = $this->delete();
            // $this->logMessage('info', 'Deleted: ' . get_class($this), $this->toArray());
            return $result;
        } catch (Exception $e) {
            $this->logMessage('error', 'Error deleting: ' . get_class($this), ['error' => $e->getMessage()]);
            return ['errors' => 'There was an error deleting the record.'];
        }
    }

    /**
     * Force delete a record
     *
     * @param int $id
     * @return bool|array
     */
    public static function forceDeleteRecord($id)
    {
        $instance = static::withTrashed()->findOrFail($id);

        try {
            $result = $instance->forceDelete();
            // $instance->logMessage('info', 'Force Deleted: ' . get_class($instance), $instance->toArray());
            return $result;
        } catch (Exception $e) {
            $instance->logMessage('error', 'Error force deleting: ' . get_class($instance), ['error' => $e->getMessage()]);
            return ['errors' => 'There was an error force deleting the record.'];
        }
    }

    /**
     * Restore a soft deleted record
     *
     * @param int $id
     * @return bool|array
     */
    public static function restoreRecord($id)
    {
        $instance = static::withTrashed()->findOrFail($id);

        try {
            $result = $instance->restore();
            // $instance->logMessage('info', 'Restored: ' . get_class($instance), $instance->toArray());
            return $result;
        } catch (Exception $e) {
            $instance->logMessage('error', 'Error restoring: ' . get_class($instance), ['error' => $e->getMessage()]);
            return ['errors' => 'There was an error restoring the record.'];
        }
    }

    // ****************************************************************************
    // ******************************** LIST DATA *********************************
    // ****************************************************************************
    // ****************************************************************************
    /**
     * List records with filters, relationships, and pagination.
     *
     * This method allows for flexible retrieval of model records, supporting various filters,
     * sorting options, and eager loading of related models.
     *
     * @param array $params Parameters for filtering, sorting, and pagination.
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated list of records.
     *
     * Example usage:
     * $params = [
     *     'where' => ['status' => 'published', 'status' => 'published'], // or [ 'views' => [ '>', 1000], 'name' => ['like', '%keyword%'] ]
     *     'order' => ['created_at' => 'desc', 'likes' => 'asc'],
     *     'has' => ['comments'], // or ['comments' => ['>', 10]] // gets posts with comments (comments is a relation) or posts with comments more than 10
     *     'limit' => 15, // no limit or limit = 0, will list all the records
     *     'page' => 1,
     *     'pagination_name' => 'posts_page' // Custom Name for pagination
     *     'with' => [ // relations with the post model
     *         'author' => [
     *             'where' => ['name' => 'John Doe'],
     *             'order' => ['created_at' => 'desc'],
     *             'limit' => 5,
     *         ],
     *         'comments' => [
     *             'where' => ['likes_count' => ['>', 10]],
     *             'order' => ['created_at' => 'asc'],
     *             'limit' => 10,
     *             'with_count' => [
     *                  'replies' => 'replies', // count all the replies of the comment
     *                  'replies as replies_custom_query' => [ // custom count with condition
     *                      'where' => ['body' => ['like', '%Dolores%']]
     *                  ]
     *             ],
     *             'having' => [
     *                   'replies_custom_query' => ['>', 15] // condition to return only the comments that have a replies_custom_query of more than 15
     *             ],
     *             'with' => [ // relation with the comments model
     *                 'replies' => [
     *                     'where' => ['status' => 'approved'],
     *                     'order' => ['created_at' => 'desc'],
     *                     'limit' => 10 // simple limit
     *                 ]
     *             ]
     *         ]
     *     ],
     *     'with_count' => [
     *          'comments' => 'comments', // return count of comments of the post, this will add an attribute to each Post model called 'comments_count'
     *          'comments as comments_custom_query' => [ // return count of comments with a condition
     *              'where' => ['body' => ['like', '%Dolores%']]
     *          ]
     *      ],
     *      'having' => [
     *          'comments_custom_query' => ['>', 10] // condition to return only posts that have a comments_custom_query of more than 10
     *      ]
     * ];
     * 
     * $posts = Post::listRecords($params);
     */
    public static function listRecords(array $params)
    {
        $query = static::query();

        // Apply filters, sorting, and relations
        $query = self::applyFilters($query, $params['where'] ?? []);
        $query = self::applyRelations($query, $params['with'] ?? []);
        $query = self::applyWithCount($query, $params['with_count'] ?? []);
        $query = self::applySorting($query, $params['order'] ?? []);
        $query = self::applyHasConditions($query, $params['has'] ?? []);
        $query = self::applyHaving($query, $params['having'] ?? []);

        // Apply limit and pagination
        $query = self::applyLimit($query, $params['limit'] ?? null, $params['page'] ?? 1, $params['pagination_name'] ?? 'page');

        return $query->get();
    }

    /**
     * applies where condition 
     * @param mixed $query
     * @param array $filters
     * @return mixed
     */
    protected static function applyFilters($query, array $filters)
    {
        foreach ($filters as $field => $condition) {
            if (is_array($condition)) {
                $query->where($field, $condition[0], $condition[1]);
            } else {
                $query->where($field, $condition);
            }
        }

        return $query;
    }

    /**
     * Apply relations and their conditions recursively
     * @param mixed $query
     * @param array $relations
     * @return mixed
     */
    protected static function applyRelations($query, array $relations)
    {
        foreach ($relations as $relation => $relationParams) {
            $query->with([
                $relation => function ($q) use ($relationParams) {
                    if (isset($relationParams['where'])) {
                        self::applyFilters($q, $relationParams['where']);
                    }
                    if (isset($relationParams['with'])) {
                        self::applyRelations($q, $relationParams['with']);
                    }
                    if (isset($relationParams['with_count'])) {
                        self::applyWithCount($q, $relationParams['with_count']);
                    }
                    if (isset($relationParams['order'])) {
                        self::applySorting($q, $relationParams['order']);
                    }
                    if (isset($relationParams['has'])) {
                        self::applyHasConditions($q, $relationParams['has']);
                    }
                    if (isset($relationParams['having'])) {
                        self::applyHaving($q, $relationParams['having'] ?? []);
                    }
                    if (isset($relationParams['limit'])) {
                        $q->limit($relationParams['limit']);
                        // i'm tired of this bitch, couldn't make the pagination work with subqueries so i used simple limits
                        // self::applyLimit($q, $relationParams['limit'] ?? null, $relationParams['page'] ?? 1, $relationParams['pagination_name'] ?? 'page');
                    }
                }
            ]);
        }

        return $query;
    }

    // Apply "order by" conditions
    /**
     * Apply order by 
     * @param mixed $query
     * @param array $orders
     * @return mixed
     */
    protected static function applySorting($query, array $orders)
    {
        foreach ($orders as $field => $direction) {
            $query->orderBy($field, $direction);
        }

        return $query;
    }

    /**
     * Apply "has" conditions for relations
     * @param mixed $query
     * @param array $hasConditions
     * @return mixed
     */
    protected static function applyHasConditions($query, array $hasConditions)
    {
        foreach ($hasConditions as $relation => $condition) {
            if (is_array($condition)) {
                $query->has($relation, $condition[0], $condition[1]);
            } else {
                $query->has($relation);
            }
        }

        return $query;
    }

    /**
     * Apply pagination and limit
     * @param mixed $query
     * @param mixed $limit
     * @param mixed $page
     * @param mixed $paginationName
     * @return mixed
     */
    protected static function applyLimit($query, $limit, $page, $paginationName)
    {
        if ($limit && $limit > 0) {
            $query->paginate($limit, ['*'], $paginationName, $page);
        }

        return $query;
    }

    /**
     * Apply "withCount" for relations count
     * @param mixed $query
     * @param array $withCounts
     * @return mixed
     */
    protected static function applyWithCount($query, array $withCounts)
    {
        foreach ($withCounts as $relation => $relationParams) {
            if (is_array($relationParams)) {
                // With filters and sorting for counts
                $query->withCount([
                    $relation => function ($q) use ($relationParams) {
                        if (isset($relationParams['where'])) {
                            self::applyFilters($q, $relationParams['where']);
                        }
                        // if (isset($relationParams['order'])) {
                        //     self::applySorting($q, $relationParams['order']);
                        // }
                    }
                ]);
            } else {
                // Simple withCount
                $query->withCount($relation);
            }
        }

        return $query;
    }

    /**
     * Apply "having" conditions
     * @param mixed $query
     * @param array $havings
     * @return mixed
     */
    protected static function applyHaving($query, array $havings)
    {
        foreach ($havings as $field => $condition) {
            if (is_array($condition)) {
                $query->having($field, $condition[0], $condition[1]);
            } else {
                $query->having($field, '=', $condition);
            }
        }

        return $query;
    }

    // ****************************************************************************
    // ****************************** DATA SEEDING ********************************
    // ****************************************************************************
    // ****************************************************************************
    /**
     * Seed the data of the model, you can pass an array called override 
     * to override the faker generated data
     * @param array $overrides ['attribute' => value, ...]
     * @return void
     */
    // example of office model seeder configuration
    // example of data seeding for an office that has multiple doctors, patients and foreach doctor can have reviews
    // $office_seeding_data = [
    //     'count' => 5, // how many offices we gonna create
    //     'attributes' => [
    //         // 'attribute_name' => ['data' => 'faker function as a string', 'unique' => boolean],
    //         'name' => ['data' => 'company', 'unique' => true], // data is the faker function name, 'unique' if true checks the database to see if the generated data is unique
    //         'code' => ['data' => 'regexify', 'params' => ['[A-Z0-9]{5}'], 'unique' => true], // params are the params for the faker function 'regexify'
    //         'address' => 'Exact value of the address', // if a string is passed the attribute(s) would have the exact value
    //         'phone' => function () { // if a closure is passed, the value of the attribute would be the result of the closure
    //             return \App\Models\Author::find(1)->status === 1 ? fake()->phoneNumber() : '555-555-555';
    //         }
    //     ],
    //     'relations' => [ // data for the relations on the office model
    //         'patients' => [ // an office can have multiple patients
    //             'type' => \Illuminate\Database\Eloquent\Relations\HasMany::class,
    //             'count' => 10, //  number of patients to be created foreach office
    //             'attributes' => [ // attributes of the patient model
    //                 'name' => ['data' => 'name'],
    //                 'email' => ['data' => 'email', 'unique' => true],
    //             ],
    //         ],

    //         'doctors' => [ // an office has one or multiple doctors, also the relation name is this key (eg: $office->doctors())
    //             'type' => \Illuminate\Database\Eloquent\Relations\BelongsToMany::class, // type of the relation with the office, many to many relation
    //             'assign' => [1, 11, 20], // this is an array of doctor ids to be assigned to the office, if this is set, we dont generate any related models, we dont need ('count', 'attributes' and 'pivot'), 
    //             // 'assign' => [ // if we have pivot data, assyn can be set like this example below
    //             //     1 => ['is_main' => true],
    //             //     2 => ['is_main' => false],
    //             //     3 => ['is_main' => false]
    //             // ],
    //             // 'assign' => function($post) { // the assign key can also be a closure to run query like this example below
    //             //     return [
    //             //         \App\Models\Tag::inRandomOrder()->first()->id => ['is_main_office' => true],
    //             //         \App\Models\Tag::inRandomOrder()->first()->id => ['is_main_office' => false],
    //             //         \App\Models\Tag::inRandomOrder()->first()->id => ['is_main_office' => false]
    //             //     ];
    //             // },
    //             'count' => 10, // maximum number of doctors to be created foreach office
    //             'attributes' => [ // attributes of the doctor model, this key is ignored if the key 'assign' is set. (we dont need to create doctors)
    //                 'name' => ['data' => 'name'],
    //                 'email' => ['data' => 'email', 'unique' => true],
    //             ],
    //             'pivot' => function ($doctor) { // pivot data can be passed as a function that accepts the generated relation model, and returns an array of pivot data
    //                 return [
    //                     // here an office has only one main doctor, so we can run a query to see if the current doctor has a main office
    //                     'is_main_office' => $doctor->offices()->wherePivot('is_main_office', true)->exists() ? false : true,
    //                 ];
    //             },

    //             'relations' => [ // data for the relations on the doctor model
    //                 'reviews' => [ // a doctor can have multiple reviews
    //                     'type' => \Illuminate\Database\Eloquent\Relations\HasMany::class,
    //                     'count' => 5, // how many reviews we create for each doctor
    //                     'attributes' => [ // attributes of the review model that belongs to the current doctor
    //                         'score' => ['data' => 'numberBetween', 'params' => [50, 100]],
    //                     ],
    //                 ],
    //             ],

    //         ],
    //     ],
    // ];

    /**
     * get seeding params
     * 
     * @return array
     */
    protected function getSeedingConfig()
    {
        if (empty($this->bmt_seeding)) {
            $this->bmt_seeding = Config::get("application.models.{$this->getClassName()}.seed", []);
        }

        return $this->bmt_seeding;
    }


    /**
     * Seeds the model's data based on the given parameters.
     *
     * @param array $params
     */
    public static function seedData(array $params = [])
    {
        $instance = new static;
        $faker = fake();
        $params = empty($params) ? $instance->getSeedingConfig() : $params;
        $count = $params['count'] ?? 1;

        // dd($params);

        for ($i = 0; $i < $count; $i++) {
            $attributes = self::generateAttributes($instance, $params['attributes'] ?? [], $faker);
            // dd($attributes);
            $newModel = $instance::create($attributes);

            // Handle relations if provided
            if (isset($params['relations'])) {
                self::generateRelations($newModel, $params['relations'], $faker);
            }
        }
    }

    /**
     * Generate attributes for a model based on provided data.
     */
    protected static function generateAttributes($model, array $attributes, $faker)
    {
        $generatedAttributes = [];

        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                $generatedAttributes[$key] = $value; // Exact value
            } elseif (is_callable($value)) {
                $generatedAttributes[$key] = $value(); // Closure execution
            } elseif (is_array($value)) {
                // Faker function and uniqueness handling
                $data = $value['data'] ?? null;
                $params = $value['params'] ?? [];
                $unique = $value['unique'] ?? false;

                if ($data) {
                    $fakerValue = $faker->{$data}(...$params);
                    if ($unique) {
                        $fakerValue = self::generateUniqueValue($model, $key, $fakerValue, $faker);
                    }
                    $generatedAttributes[$key] = $fakerValue;
                }
            }
        }

        return $generatedAttributes;
    }

    /**
     * Ensure the uniqueness of an attribute value.
     */
    protected static function generateUniqueValue($model, string $attribute, $value, $faker)
    {
        while ($model::where($attribute, $value)->exists()) {
            $value = $faker->$attribute;
        }
        return $value;
    }

    /**
     * Generate relations for a model based on provided data.
     */
    protected static function generateRelations($model, array $relations, $faker)
    {
        foreach ($relations as $relationName => $relationData) {
            $relationType = $relationData['type'] ?? null;

            if ($relationType) {
                // Check if we are assigning existing models
                if (isset($relationData['assign'])) {
                    if (is_array($relationData['assign'])) {
                        $model->$relationName()->sync($relationData['assign'], false); // Assign existing
                    } else if (is_callable($relationData['assign'])) {
                        $model->$relationName()->sync($relationData['assign']($model), false); // Assign existing
                    }

                } else {
                    // Generate new related models
                    $relatedModel = $model->$relationName()->getRelated();
                    $count = $relationData['count'] ?? 1;

                    for ($i = 0; $i < $count; $i++) {
                        $relatedAttributes = self::generateAttributes($relatedModel, $relationData['attributes'] ?? [], $faker);

                        // if not manyToMany Relation
                        if ($relationType !== \Illuminate\Database\Eloquent\Relations\BelongsToMany::class) {
                            $relatedInstance = $model->$relationName()->create($relatedAttributes);
                        } else { // if it is, we create a stand alone model, and attach the ids later
                            $relatedInstance = $relatedModel::create($relatedAttributes);
                        }

                        // If pivot data exists and check if the relation type is manyToMany
                        if ($relationType === \Illuminate\Database\Eloquent\Relations\BelongsToMany::class) {
                            if (isset($relationData['pivot'])) {
                                $pivotData = $relationData['pivot']($model);
                                $model->$relationName()->attach($relatedInstance->id, $pivotData);
                            } else {
                                $model->$relationName()->attach($relatedInstance->id);
                            }
                        }

                        // Recursively generate relations of related models
                        if (isset($relationData['relations'])) {
                            self::generateRelations($relatedInstance, $relationData['relations'], $faker);
                        }
                    }
                }
            }
        }
    }

    // ****************************************************************************
    // ****************************** DATA VALIDATION *****************************
    // ****************************************************************************
    // ****************************************************************************

    /**
     * Load validation rules and messages from the configuration file.
     */
    // example for a validation rule in the config file
    // 'validation' => [
    //     'rules' => [
    //         'name' => 'required|string|max:255', // or ['required', 'string', 'max:255']
    //         'email' => 'required|email|unique:example_models,email',
    //         'age' => 'nullable|integer|min:18',
    //     ],
    //     'messages' => [
    //         'name.required' => 'The name field is required.',
    //         'email.required' => 'The email field is required.',
    //         'email.email' => 'The email :email is not valid.',
    //         'age.min' => 'You must be at least 18 years old.',
    //     ],
    // ],
    protected function loadValidationConfig()
    {
        if (empty($this->bmt_validation)) {
            $this->bmt_validation = config("application.models.{$this->getClassName()}.validation", []);

            // Ensure we have arrays for rules and messages
            $this->bmt_validation['rules'] ??= [];
            $this->bmt_validation['messages'] ??= [];
        }

        return $this->bmt_validation;
    }

    /**
     * Validate the model's attributes.
     *
     * @param array $data
     * @param bool $redirectBackWithErrors
     * @return mixed
     * @throws ValidationException
     */
    public function validateAttributes(array $data, $redirectBackWithErrors = true, $returnAsJSON = false)
    {
        $validation = $this->loadValidationConfig();
        $validator = Validator::make($data, $validation['rules'], $validation['messages']);

        if ($validator->fails() && $redirectBackWithErrors === true) {
            return $this->handleValidationErrors($validator, $returnAsJSON);
        } else {
            // as array
            return $validator;
        }
    }

    /**
     * Handle validation errors.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @param bool return as JSON
     * @throws ValidationException
     */
    protected function handleValidationErrors($validator, $returnAsJSON)
    {
        $errors = $validator->errors();

        // with JSON
        if ($returnAsJSON) {
            return response()->json(['errors' => $errors], 422);
        }

        // redirect back with errors
        throw new ValidationException($validator, back()->withErrors($errors)->withInput());
    }




    // ****************************************************************************
    // ****************************** SAFE EXECUTION ******************************
    // ****************************************************************************
    // ****************************************************************************
    /**
     * Execute a closure or function with HTTP error handling.
     *
     * @param callable $callback The function or closure to execute.
     * @param array $options Options for error handling, e.g., 'redirect', 'message', etc.
     * @return mixed The result of the callback if no error occurs.
     * @throws Exception If the error handling strategy is 'throw'.
     */
    // option params
    // $options['strategy'] // ['redirect', 'abort', 'throw'] 
    // redirect => to a safe route. | abort => 500 error | throw => yeet that error | default is redirect to route home
    // $options['route'] // route to redirect to if any errors were generated
    // $options['route_params'] // params of the redirecting route
    // $options['message'] // some error message to display, default is Ooops!!
    public function executeWithHandling(callable $callback, array $options = [])
    {
        try {
            // Execute the callback function/closure
            return $callback();
        } catch (Exception $e) {
            // Handle the error based on the provided options
            return $this->handleExecutionError($e, $options);
        }
    }

    /**
     * Handle errors during execution.
     *
     * @param Exception $exception The caught exception.
     * @param array $options Options for error handling.
     * @return mixed The result of the error handling strategy.
     * @throws Exception If the error handling strategy is 'throw'.
     */
    protected function handleExecutionError(Exception $exception, array $options)
    {
        // Log the error
        $this->logMessage('error', $exception->getMessage(), ['exception' => $exception]);

        // Default error handling options
        // $strategy = $options['strategy'] ?? 'redirect';
        $route = $options['route'] ?? 'home';
        $route_params = $options['route_params'] ?? [];
        $message = $options['message'] ?? 'Ooops!! something went wrong';

        if (!empty($route)) {
            $this->handleRedirecting('error', $route, $route_params, $message, 500);
        }

        return $exception;
    }


    // ****************************************************************************
    // ****************************** REDIRECTION *********************************
    // ****************************************************************************
    // ****************************************************************************
    /**
     * Handle messaging and redirection.
     *
     * @param string|null $route The route to redirect to.
     * @param int $statusCode The HTTP status code (e.g., 500, 301).
     * @param string|null $message The message to display (optional).
     * @param bool $isQuiet If true, suppress the display of any messages.
     * @return mixed The redirect response or other actions.
     */
    // protected function handleMessaging($route = null, $statusCode = 302, $message = null, $isQuiet = false)
    protected function handleRedirecting($type = 'info', $route = 'home', $route_params = [], $message = '', $statusCode = 302)
    {
        if ($message) {
            Session::flash($type, $message);
        }

        // // Redirect to the specified route or abort with the status code
        return $route ? Redirect::route($route, $route_params, $statusCode) : abort($statusCode, $message);
    }


    // ****************************************************************************
    // ******************************** LOGGIN ************************************
    // ****************************************************************************
    // ****************************************************************************
    /**
     * get loggin params
     * 
     * @return array
     */
    // 'log' => [
    //     'everything' => true, // when true it looks inside the ignore array. if the level of the error is present, it ignores it
    //      // if the everything is false, nothing will be logged
    //     'ignore' => [
    //         'emergency'
    //         // alert, critical, error, warning, notice, info, debug, log
    //     ],
    // ],
    protected function getLogConfig()
    {
        if (empty($this->bmt_log)) {
            $this->bmt_log = Config::get("application.models.{$this->getClassName()}.log", []);
        }

        return $this->bmt_log;
    }


    /**
     * Log messages to a specific file based on the model's name.
     *
     * @param string $level The log level (e.g., emergency, alert, critical, error, warning, notice, info, debug, log).
     * @param string $message The message to log.
     * @return void
     */
    protected function logMessage($level, $message, $context = [], $forced = false)
    {
        $logParams = $this->getLogConfig();
        $everything = $logParams['everything'] ?? true;
        $ignore = (empty($logParams['ignore']) || !is_array($logParams['ignore'])) ? [] : $logParams['ignore'];


        // check if can log the class
        if (!$forced && (!$everything || in_array($level, $ignore))) {
            return;
        }

        $modelClass = (new \ReflectionClass($this))->getShortName();
        $logFilePath = storage_path("logs/application/{$modelClass}.log");

        // Ensure the directory exists
        if (!File::exists(dirname($logFilePath))) {
            File::makeDirectory(dirname($logFilePath), 0755, true);
        }

        // Log to the specific file
        Log::build([
                    'driver' => 'single',
                    'path' => $logFilePath,
                    'level' => $level,
                ])->$level($message, $context);
    }


    // ****************************************************************************
    // ******************************** CACHING ***********************************
    // ****************************************************************************
    // ****************************************************************************

    /**
     * Get the cache configuration
     * 
     * @return array caching
     */
    protected function getCachingConfig()
    {
        if (empty($this->bmt_caching)) {
            $this->bmt_caching = Config::get("application.models.{$this->getClassName()}.caching", []);
        }

        return $this->bmt_caching;
    }
    /**
     * Get the cache key for the model instance or custom query.
     *
     * @param string|null $customKey
     * @return string
     */
    protected function getCacheKey($customKey = null)
    {
        $baseKey = 'cache_model_' . static::class . '.base_key';

        return $customKey ? "{$baseKey}_{$customKey}" : "{$baseKey}_{$this->getKey()}";
    }

    /**
     * Check if caching is enabled for the model.
     *
     * @return bool
     */
    protected function isCachingEnabled()
    {
        $conf = $this->getCachingConfig();

        return $conf['active'] ?? true;
    }

    /**
     * Retrieve a model instance or query result from the cache, or store it if not cached.
     *
     * @param string|null $customKey
     * @param \Closure $callback
     * @return mixed
     */
    // use case
    // $postModel = Post::find(1);
    // $postsWithAuthors = $postModel->cacheModel('post_with_author_and_condition', function () {
    //     if ($this->status === 'active') {
    //         return $this->load('author');  // Load the author only if the post is active
    //     }
    //     return $this;  // Return the post without loading the author
    // });
    // 
    // $postModel = new Post();
    // $postsWithAuthors = $postModel->cacheModel('posts_with_authors', function () {
    //     return $this->where('status', 'active')
    //                 ->orderBy('created_at', 'desc')
    //                 ->get()
    //                 ->each(function ($post) {
    //                     $post->load('author');  // Load the author for each post in the collection
    //                 });
    // });

    // // Further chaining Eloquent methods on the result
    // $filteredPosts = $paginatedPosts->where('category', 'Technology');
    public function cacheModel($customKey = null, $ttl = null, \Closure $callback)
    {
        if ($this->isCachingEnabled()) {
            $conf = $this->getCachingConfig();
            $cacheKey = $this->getCacheKey($customKey);
            $ttl ??= $conf['for_seconds'] ?? 60;

            return Cache::remember($cacheKey, $ttl, function () use ($callback) {
                $result = $callback();

                // Ensure that the result is queryable
                return $result instanceof \Illuminate\Database\Eloquent\Builder ? $result : $this->newQuery()->whereKey($result->getKey());
            });
        }

        return $callback();
    }

    /**
     * Clear the cache for this model or a custom query.
     *
     * @param string|null $customKey
     * @return bool
     */
    public function clearModelCache($customKey = null)
    {
        if ($this->isCachingEnabled()) {
            return Cache::forget($this->getCacheKey($customKey));
        }

        return false;
    }


    // ****************************************************************************
    // ***************************** VIEW RENDERING *******************************
    // ****************************************************************************
    // ****************************************************************************
    // use case
    // $params = [
    //     "[rowID1]" => [ // row always spans a full width
    //         "title" => "row title", // can be empty
    //         "paragraph" => "some paragraph explainig the content block", // empty is the default value
    //          "class" => "class or-classes for-the-row", // default is emprt
    //         "content" => [ // content of the row, foreach content has the same value of bootstrap column span col-md-X
    //             "[contentID1]" => [
    //                 // the amout out of 100 that this piece of content takes inside the parent row container, 
    //                 // the other pieces of content left can share the remaning space unless one of them has the span key 
    //                 // for example if we have 3 contents and one of the has the span of 5 cols, 
    //                 "span" => 5, // col-md-5
    //                 "breackpoint" => "md" // sm/md/lg/xl/xxl default is md
    //                 "class" => '' // custom class for the content
    // 
    //                 "template" => "path_to_blade_view_file.blade.php", // if the key template is passed and not empty, we pass the data and render the blade view 
    //                 "data" => [ // some array of data that get passed to the view
    //                     'title' => 'Posts List',
    //                     'count' => 10,
    //                 ],
    //             ],
    //             "[contentID2]" => [
    //                 "rows" => [ // if the key row is passed we nedd to recusion to render the subrow that is inside contentID2
    //                     // nested row/rows params
    //                 ]
    //             ]
    //         ]
    //     ],
    //     "[rowID2]" => [
    //         "title" => "row title", // can be empty
    //         "paragraph" => "some paragraph explainig the content block", // empty is the default value
    //         "content" => [
    //             "[contentID1]" => [
    //                 "template" => "path_to_blade_view_file.blade.php",
    //                 "data" => [
    //                     'title' => 'menu',
    //                     'elements' => [
    // 
    //                     ]
    //                 ],
    //             ],
    //             "[contentID2]" => [
    //                 "template" => "path_to_blade_view_file.blade.php",
    //                 "data" => [
    //                     'title' => 'comments List',
    //                     'count' => 10,
    //                 ],
    //             ]
    //         ]
    //     ],
    // ];
    // public static function renderElement(array $params)
    // {
    //     return self::renderRows($params);
    // }

    // protected static function renderRows(array $rows)
    // {
    //     $html = '';

    //     foreach ($rows as $rowID => $row) {
    //         $rowID = (!empty($rowID) && is_string($rowID) && strlen($rowID) > 4) ? $rowID : substr(md5(time() . rand()), 0, 8);
    //         $class = $row['class'] ?? '';
    //         $html .= '<div id="' . $rowID . '" class="row ' . $class . '">';

    //         // If the row has a title or paragraph, render them
    //         if (!empty($row['title'])) {
    //             $html .= '<h2>' . htmlspecialchars($row['title']) . '</h2>';
    //         }
    //         if (!empty($row['paragraph'])) {
    //             $html .= '<p>' . htmlspecialchars($row['paragraph']) . '</p>';
    //         }

    //         // Process the row's content
    //         if (!empty($row['content'])) {
    //             $html .= self::renderContent($row['content']);
    //         }

    //         $html .= '</div>';
    //     }

    //     return $html;
    // }

    // protected static function renderContent(array $content)
    // {
    //     $html = '';

    //     foreach ($content as $contentID => $item) {
    //         $breackpoint = $item['breackpoint'] ?? 'md';
    //         $span = $item['span'] ?? 12; // Default to full width if no span is provided
    //         $class = $row['class'] ?? '';
    //         $html .= '<div class="col-' . $breackpoint . '-' . (int) $span . ' ' . $class . '">';

    //         if (!empty($item['template'])) {
    //             $html .= View::make($item['template'], $item['data'] ?? [])->render();
    //         }

    //         if (!empty($item['rows'])) {
    //             $html .= self::renderRows($item['rows']);
    //         }

    //         $html .= '</div>';
    //     }

    //     return $html;
    // }



    // ****************************************************************************
    // **************************** DATA EXTRACTION *******************************
    // ****************************************************************************
    // ****************************************************************************

    /**
     * Extracts attributes dynamically using dot notation, supporting nested collections.
     *
     * @param \Illuminate\Support\Collection $collection
     * @param string $dotNotation
     * @return mixed
     */
    public function dynamicExtract($collection, string $dotNotation)
    {
        // if (!($collection instanceof Collection)) {
        //     $collection = collect($collection);
        // }

        // $segments = explode('.', $dotNotation);

        // return $collection->map(function ($item) use ($segments) {
        //     return $this->resolveNestedValues($item, $segments);
        // });

        $segments = explode('.', $dotNotation);

        if ($collection instanceof Collection) {
            // If the data is a collection, map over each item
            return $collection->map(function ($item) use ($segments) {
                return $this->resolveNestedValues($item, $segments);
            });
        }

        // If the data is a single item, resolve directly
        return $this->resolveNestedValues($collection, $segments);
    }

    /**
     * Recursively resolves attributes from an item based on dot notation segments.
     *
     * @param mixed $item
     * @param array $segments
     * @return mixed
     */
    protected function resolveNestedValues($item, array $segments)
    {
        if (empty($segments)) {
            return $item;
        }

        $currentSegment = array_shift($segments);

        $value = data_get($item, $currentSegment);

        // // If the current segment points to a collection or array, map over it
        // if ($item instanceof Collection || is_array($item)) {
        //     return collect($item)->map(function ($subItem) use ($segments, $currentSegment) {
        //         return $this->resolveNestedAttributes(data_get($subItem, $currentSegment), $segments);
        //     })->all();
        // }

        // // Otherwise, resolve the current segment normally
        // return $this->resolveNestedAttributes(data_get($item, $currentSegment), $segments);

        // If the value is a collection or array, map over it and resolve further
        if ($value instanceof Collection || is_array($value)) {
            return collect($value)->map(function ($subItem) use ($segments) {
                return $this->resolveNestedValues($subItem, $segments);
            })->all();
        }

        // Otherwise, resolve the remaining segments
        return $this->resolveNestedValues($value, $segments);
    }




    // ****************************************************************************
    // ******************************* DATA EXPORT ********************************
    // ****************************************************************************
    // ****************************************************************************
    /**
     * Get the cache configuration
     * 
     * @return array caching
     */
    protected function getExportConfig()
    {
        if (empty($this->bmt_export)) {
            $this->bmt_export = Config::get("application.models.{$this->getClassName()}.export", []);
        }

        return $this->bmt_export;
    }


    /**
     * Export data to CSV or Excel.
     *
     * @param mixed $data The data to be exported.
     * @param array $columns Array of columns with data keys and translated labels.
     * @param string $exportType 'csv' or 'excel'.
     * @param bool $useJob Whether to offload the export process to a job.
     * @return Response|void
     */
    public function exportData($data, $columns = [], string $exportType = 'csv', bool $useJob = false)
    {
        if (!($data instanceof Collection)) {
            $data = collect($data);
        }

        if (empty($columns)) {
            $columns = $this->getExportConfig();
        }


        // dd([
        //     'useJob' => $useJob,
        //     'exportType' => $exportType,
        //     'data' => $data,
        //     'columns' => $columns,
        // ]);

        if ($useJob) {
            // Offload the export process to a background job
            // $this->exportViaJob($data, $columns, $exportType);
            // dd("job export not implemented yet");
            // Queue::push(new ExportDataJob($data, $columns, $exportType));

        } else {
            // Perform the export synchronously using chunking
            return $this->exportViaChunking($data, $columns, $exportType);
        }
    }


    /**
     * Export data using chunking (synchronously).
     *
     * @param array|Collection $data
     * @param array $columns
     * @param string $exportType // csv or xlsx
     * @return Response
     */
    protected function exportViaChunking($data, array $columns, string $exportType): Response
    {
        // prepare data for export, (callbacks)
        $preparedData = $this->prepareDataForExport($data, $columns);

        return $this->exportPreparedData($preparedData, $columns, $exportType);
    }


    /**
     * Prepare data for export by selecting only the necessary columns.
     *
     * @param array|Collection $data
     * @param array $columns
     * @return array
     */
    protected function prepareDataForExport($data, array $columns): array
    {
        // Prepare data in chunks for memory-efficient processing
        $preparedData = [];
        // define the chunk size
        $chunkSize = self::getAppProperties('data-chunks.normal');
        // define the data ceparation for the items with multiple values
        $dataSeparation = self::getAppProperties('exports.data-separation');

        // dd([
        //     'chunksize' => $chunkSize,
        //     'data' => $data,
        //     'columns' => $columns,
        // ]);

        // dd([
        //     'comment_body' => $this->dynamicExtract($data, 'comments.body'),
        //     '$item' => $data
        // ]);

        if ($data instanceof Collection) {
            foreach ($data->chunk($chunkSize) as $chunkedData) {
                foreach ($chunkedData as $item) {
                    $row = [];
                    foreach ($columns as $key => $columnDefinition) {
                        // Retrieve the label and callback (if present)
                        // $label = $columnDefinition['label'] ?? $key;
                        $take = $columnDefinition['take'] ?? 'all';
                        $callback = $columnDefinition['callback'] ?? null;

                        // Get the value from the data item
                        $extracted_value = $this->dynamicExtract($item, $key);

                        if (is_array($extracted_value)) {
                            if ($take === 'first') {
                                $extracted_value = $extracted_value[0];

                                if (is_array($extracted_value)) {
                                    foreach ($extracted_value as $key2 => $extract_val) {
                                        $value .= is_callable($callback) ? $callback($extract_val, $item) : $extract_val;

                                        if ($key2 !== array_key_last($extracted_value)) {
                                            $value .= $dataSeparation;
                                        }
                                    }
                                } else {
                                    $value = is_callable($callback) ? $callback($extracted_value, $item) : $extracted_value;
                                }
                            }

                            if ($take === 'last') {
                                $extracted_value = end($extracted_value);

                                if (is_array($extracted_value)) {
                                    foreach ($extracted_value as $key2 => $extract_val) {
                                        $value .= is_callable($callback) ? $callback($extract_val, $item) : $extract_val;

                                        if ($key2 !== array_key_last($extracted_value)) {
                                            $value .= $dataSeparation;
                                        }
                                    }
                                } else {
                                    $value = is_callable($callback) ? $callback($extracted_value, $item) : $extracted_value;
                                }
                            }

                            // The extracted is an array, so we iterate and pass data through the callback
                            if ($take === 'all') {
                                foreach ($extracted_value as $key2 => $extract_val) {
                                    // dd($extracted_value, $extract_val);

                                    if (is_array($extract_val)) {
                                        foreach ($extract_val as $key3 => $extract_val2) {
                                            $value .= is_callable($callback) ? $callback($extract_val2, $item) : $extract_val2;

                                            if ($key3 !== array_key_last($extract_val)) {
                                                $value .= $dataSeparation;
                                            }
                                        }
                                    } else {
                                        $value .= is_callable($callback) ? $callback($extract_val, $item) : $extract_val;
                                    }

                                    if ($key2 !== array_key_last($extracted_value)) {
                                        $value .= $dataSeparation;
                                    }
                                }
                            }
                        } else {
                            $value = is_callable($callback) ? $callback($extracted_value, $item) : $extracted_value; // Pass the value and the whole item if needed
                        }

                        // Add the final value to the row
                        $row[$key] = $value;
                    }

                    // dd([
                    //     'row' => $row,
                    //     'comments.body' => $this->dynamicExtract($item, 'comments.body'),
                    //     'comments.replies.body' => $this->dynamicExtract($item, 'comments.replies.body'),
                    //     $item->toArray()
                    // ]);
                    $preparedData[] = $row;
                }
            }
        }

        // dd($preparedData);
        // retrun the prepared data for export
        return $preparedData;
    }


    /**
     * Export prepared data to CSV or Excel.
     *
     * @param array $preparedData
     * @param array $columns
     * @param string $exportType
     * @return Response
     */
    protected function exportPreparedData(array $preparedData, array $columns, string $exportType): Response
    {
        // generate a file name for the exported file
        $fileName = 'export_' . now()->timestamp . '.' . $exportType;

        // extract the columns names from the $columns array
        $labels = array_column($columns, 'label');

        return Excel::download(new class ($preparedData, $labels) implements FromArray, WithHeadings {
            private $data;
            private $labels;

            public function __construct(array $data, array $labels)
            {
                $this->data = $data;
                $this->labels = $labels;
            }

            public function array(): array
            {
                return $this->data;
            }

            public function headings(): array
            {
                return array_values($this->labels);
            }
        }, $fileName);
    }
}
