<?php

class shopMorphsearchPluginMatchesRepository
{
    /**
     * @var waModel
     */
    protected $model;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->model = new waModel();
    }

    /**
     * Make an instance of static.
     *
     * @return static
     */
    public static function factory()
    {
        return new static();
    }

    /**
     * Retrieve suggestions by given query, whether from cache or database.
     *
     * @param string $query
     * @param int $limit
     *
     * @return array
     */
    public function retrieveSuggestions($query, $limit = 10)
    {
        $cache = shopMorphsearchPluginCache::make();

        $key = 'shop_morphsearch.suggestions_' . md5(serialize([$query, $limit]));

        if (($suggestions = $cache->get($key)) !== null) {
            return $suggestions;
        }

        $suggestions = $this->findByName($query, $limit);

        $count = count($suggestions);

        if ($count < $limit) {
            $suggestions = array_merge($suggestions, $this->findByTag($query, $limit - $count));
        }

        $cache->set($key, $suggestions, 86400); // cache for 24 hours

        return $suggestions;
    }

    /**
     * Find suggestions by product name.
     *
     * @param string $query
     * @param int $limit
     *
     * @return array
     */
    public function findByName($query, $limit = 10)
    {
        $sqlString = "SELECT DISTINCT `sp`.`id`, `sp`.`name`, `sp`.`url` AS `product_url`, `sc`.`full_url` AS `category_url`
                      FROM `shop_product` AS `sp`
                      LEFT JOIN `shop_category` AS `sc` ON `sc`.`id` = `sp`.`category_id`
                      WHERE `sp`.`name` LIKE :query AND (`sp`.`count` > 0 || `sp`.`count` IS NULL) AND `sp`.`status` = 1
                      ORDER BY `sp`.`name`
                      LIMIT i:limit";

        $sqlParams = [
            'query' => '%' . $query . '%',
            'limit' => $limit
        ];

        return $this->model()->query($sqlString, $sqlParams)->fetchAll();
    }

    /**
     * Find suggestions by product tags.
     *
     * @param string $query
     * @param int $limit
     *
     * @return array
     */
    public function findByTag($query, $limit = 10)
    {
        $sqlString = "SELECT DISTINCT `sp`.`id`, `sp`.`name`, `sp`.`url` AS `product_url`, `sc`.`full_url` AS `category_url`
                      FROM `shop_product` AS `sp`
                      INNER JOIN `shop_product_tags` AS `spt` ON `sp`.`id` = `spt`.`product_id`
                      INNER JOIN `shop_tag` AS `st` ON `st`.`id` = `spt`.`tag_id` AND `st`.`name` LIKE :query
                      LEFT JOIN `shop_category` AS `sc` ON `sc`.`id` = `sp`.`category_id`
                      WHERE `sp`.`name` NOT LIKE :query AND (`sp`.`count` > 0 || `sp`.`count` IS NULL) AND `sp`.`status` = 1
                      ORDER BY `sp`.`name`
                      LIMIT i:limit";

        $sqlParams = [
            'query' => '%' . $query . '%',
            'limit' => $limit
        ];

        return $this->model()->query($sqlString, $sqlParams)->fetchAll();
    }

    /**
     * Retrieve search hash, whether from cache or database.
     *
     * Returns an array of product ids.
     *
     * @param string $query
     *
     * @return array
     */
    public function retrieveSearchHash($query)
    {
        $cache = shopMorphsearchPluginCache::make();

        $key = 'shop_morphsearch.search_hash_' . md5(serialize([$query]));

        if (($hash = $cache->get($key)) !== null) {
            return $hash;
        }

        $hash = $this->querySearchHash($query);

        $cache->set($key, $hash, 86400); // cache for 24 hours

        return $hash;
    }

    /**
     * Find search hash by given query.
     *
     * @param string $query
     *
     * @return array
     */
    public function querySearchHash($query)
    {
        $sqlString = "(
                          SELECT DISTINCT `sp`.`id`
                          FROM `shop_product` AS `sp`
                          WHERE `sp`.`name` LIKE :query AND (`sp`.`count` > 0 || `sp`.`count` IS NULL) AND `sp`.`status` = 1
                      )
                      
                      UNION

                      (
                          SELECT DISTINCT `sp`.`id`
                          FROM `shop_product` AS `sp`
                          INNER JOIN `shop_product_tags` AS `spt` ON `sp`.`id` = `spt`.`product_id`
                          INNER JOIN `shop_tag` AS `st` ON `st`.`id` = `spt`.`tag_id` AND `st`.`name` LIKE :query
                          WHERE `sp`.`name` NOT LIKE :query AND (`sp`.`count` > 0 || `sp`.`count` IS NULL) AND `sp`.`status` = 1
                      )";

        $sqlParams = [
            'query' => '%' . $query . '%',
        ];

        return array_keys($this->model()->query($sqlString, $sqlParams)->fetchAll('id'));
    }

    /**
     * Obtain repository model.
     *
     * @return waModel
     */
    public function model()
    {
        return $this->model;
    }
}
