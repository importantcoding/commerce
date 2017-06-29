<?php

namespace craft\commerce\services;

use craft\commerce\models\ShippingCategory;
use craft\commerce\records\ShippingCategory as ShippingCategoryRecord;
use craft\db\Query;
use yii\base\Component;
use yii\base\Exception;

/**
 * Shipping category service.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2015, Pixel & Tonic, Inc.
 * @license   https://craftcommerce.com/license Craft Commerce License Agreement
 * @see       https://craftcommerce.com
 * @package   craft.plugins.commerce.services
 * @since     1.0
 */
class ShippingCategories extends Component
{

    /**
     * @var bool
     */
    private $_fetchedAllShippingCategories = false;

    /**
     * @var ShippingCategory[]
     */
    private $_shippingCategoriesById = [];

    /**
     * @var ShippingCategory[]
     */
    private $_shippingCategoriesByHandle;

    /**
     * @var ShippingCategory
     */
    private $_defaultShippingCategory;

    /**
     * Returns all Shipping Categories
     *
     * @return ShippingCategory[]
     */
    public function getAllShippingCategories(): array
    {
        if (!$this->_fetchedAllShippingCategories) {
            $results = $this->_createShippingCategoryQuery()->all();

            foreach ($results as $result) {
                $shippingCategory = new ShippingCategory($result);
                $this->_memoizeShippingCategory($shippingCategory);
            }

            $this->_fetchedAllShippingCategories = true;
        }

        return $this->_shippingCategoriesById;
    }
    
    /**
     * @param int $shippingCategoryId
     *
     * @return ShippingCategory|null
     */
    public function getShippingCategoryById(int $shippingCategoryId)
    {
        if (isset($this->_shippingCategoriesById[$shippingCategoryId])) {
            return $this->_shippingCategoriesById[$shippingCategoryId];
        }

        if ($this->_fetchedAllShippingCategories) {
            return null;
        }

        $result = $this->_createShippingCategoryQuery()
            ->where(['id' => $shippingCategoryId])
            ->one();

        if (!$result) {
            return null;
        }

        $this->_memoizeShippingCategory(new ShippingCategory($result));

        return $this->_shippingCategoriesById[$shippingCategoryId];
    }
    
    /**
     * @param string $shippingCategoryHandle
     *
     * @return ShippingCategory|null
     */
    public function getShippingCategoryByHandle(string $shippingCategoryHandle)
    {
        if (isset($this->_shippingCategoriesByHandle[$shippingCategoryHandle])) {
            return $this->_shippingCategoriesByHandle[$shippingCategoryHandle];
        }

        if ($this->_fetchedAllShippingCategories) {
            return null;
        }

        $result = $this->_createShippingCategoryQuery()
            ->where(['handle' => $shippingCategoryHandle])
            ->one();

        if (!$result) {
            return null;
        }

        $this->_memoizeShippingCategory(new ShippingCategory($result));

        return $this->_shippingCategoriesByHandle[$shippingCategoryHandle];
    }

    /**
     * Get the default shipping category
     *
     * @return ShippingCategory|null
     */
    public function getDefaultShippingCategory()
    {
        if (null === $this->_defaultShippingCategory) {
            $row = $this->_createShippingCategoryQuery()
                ->where(['default' => 1])
                ->one();

            if (!$row) {
                return null;
            }

            $this->_defaultShippingCategory = new ShippingCategory($row);
        }

        return $this->_defaultShippingCategory;
    }

    /**
     * @param ShippingCategory $model
     *
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function saveShippingCategory(ShippingCategory $model): bool
    {
        $oldHandle = null;

        if ($model->id) {
            $record = ShippingCategoryRecord::findOne($model->id);

            if (!$record) {
                throw new Exception(Craft::t('commerce', 'No shipping category exists with the ID “{id}”',
                    ['id' => $model->id]));
            }

            $oldHandle = $record->handle;
        } else {
            $record = new ShippingCategoryRecord();
        }

        $record->name = $model->name;
        $record->handle = $model->handle;
        $record->description = $model->description;
        $record->default = $model->default;

        $record->validate();
        $model->addErrors($record->getErrors());

        if (!$model->hasErrors()) {
            // If this was the default make all others not the default.
            if ($model->default) {
                ShippingCategoryRecord::updateAll(['default' => 0]);
            }

            // Save it!
            $record->save(false);

            // Now that we have a record ID, save it on the model
            $model->id = $record->id;

            // Update Service cache
            $this->_memoizeShippingCategory($model);

            if (null !== $oldHandle && $model->handle != $oldHandle) {
                unset($this->_shippingCategoriesByHandle[$oldHandle]);
            }

            return true;
        }

        return false;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function deleteShippingCategoryById($id): bool
    {
        $all = $this->getAllShippingCategories();
        if (count($all) === 1) {
            return false;
        }

        $record = ShippingCategoryRecord::findOne($id);

        if ($record) {
            return (bool) $record->delete();
        }

        return false;
    }

    // Private methods
    // =========================================================================

    /**
     * Memoize a shipping category model by its ID and handle.
     *
     * @param ShippingCategory $shippingCategory
     *
     * @return void
     */
    private function _memoizeShippingCategory(ShippingCategory $shippingCategory)
    {
        $this->_shippingCategoriesById[$shippingCategory->id] = $shippingCategory;
        $this->_shippingCategoriesByHandle[$shippingCategory->handle] = $shippingCategory;
    }

    /**
     * Returns a Query object prepped for retrieving shipping categories.
     *
     * @return Query
     */
    private function _createShippingCategoryQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'description',
                'default'
            ])
            ->from(['{{%commerce_shippingcategories}}']);
    }
}
