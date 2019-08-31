<?php

class shopMorphsearchPluginFrontendSearchAction extends shopFrontendAction
{
    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->assignCollection();

        $this->setThemeTemplate('morphsearch.search.html');
    }

    /**
     * Set request collection.
     *
     * @return void
     *
     * @throws waException
     */
    protected function assignCollection()
    {
        $query = waRequest::request('query', '', waRequest::TYPE_STRING_TRIM);

        $hash = shopMorphsearchPluginMatchesRepository::factory()->retrieveSearchHash($query);

        $collection = new shopProductsCollection($hash);

        $collection->orderBy("IF (p.name LIKE '%" . (new waModel())->escape($query) . "%', 0, 1), p.name");

        $this->setSearchCollection($collection, $query);
    }

    /**
     * Set search collection. (Same as parent::setCollection(), just with some tweaks, like caching).
     *
     * @param shopProductsCollection $collection
     * @param string $query
     *
     * @throws waException
     */
    protected function setSearchCollection(shopProductsCollection $collection, $query)
    {
        $limit = (int) waRequest::cookie('products_per_page');

        if (! $limit || $limit < 0 || $limit > 500) {
            $limit = $this->getConfig()->getOption('products_per_page');
        }

        $page = waRequest::get('page', 1, 'int');

        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;

        $collection->setOptions(['overwrite_product_prices' => true]);

        $cache = shopMorphsearchPluginCache::make();

        $key = 'shop_morphsearch.search_products_' . md5(serialize([$query, $offset, $limit]));

        if (($products = $cache->get($key)) === null) {

            $products = $collection->getProducts('*,skus_filtered,skus_image', $offset, $limit);

            $cache->set($key, $products, 86400); // cache for 24 hours
        }

        $count = $collection->count();

        $pages_count = ceil((float) $count / $limit);

        $this->view->assign('pages_count', $pages_count);

        $this->view->assign('products', $products);

        $this->view->assign('products_count', $count);
    }
}
