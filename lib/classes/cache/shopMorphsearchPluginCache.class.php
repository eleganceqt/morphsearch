<?php

class shopMorphsearchPluginCache
{
    /**
     * Make a cache driver instance.
     *
     * @return waCache
     */
    public static function make()
    {
        return new waCache(new waFileCacheAdapter([]), wa()->getConfig()->getApplication());
    }
}
