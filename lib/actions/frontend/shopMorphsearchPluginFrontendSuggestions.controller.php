<?php

class shopMorphsearchPluginFrontendSuggestionsController extends waJsonController
{
    /**
     * @var int
     */
    const PER_PAGE = 10;

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $query = waRequest::request('query', '', waRequest::TYPE_STRING_TRIM);

        $suggestions = $this->retrieveSuggestions($query, self::PER_PAGE);

        $this->response['suggestions'] = $suggestions;
    }

    /**
     * Retrieve suggestions.
     *
     * @param string $query
     * @param int $limit
     *
     * @return array
     */
    protected function retrieveSuggestions($query, $limit)
    {
        $repository = shopMorphsearchPluginMatchesRepository::factory();

        $suggestions = $repository->retrieveSuggestions($query, $limit);

        $this->fillFrontendUrl($suggestions);

        return $suggestions;
    }

    /**
     * Build suggestions frontend url.
     *
     * @param array $suggestions
     *
     * @return void
     */
    protected function fillFrontendUrl(&$suggestions)
    {
        foreach ($suggestions as &$suggestion) {

            $params = [
                'product_url'  => $suggestion['product_url'],
                'category_url' => $suggestion['category_url']
            ];

            $suggestion['frontend_url'] = wa()->getRouteUrl('shop/frontend/product', $params);

            unset($suggestion['product_url'], $suggestion['category_url']);
        }
    }
}
