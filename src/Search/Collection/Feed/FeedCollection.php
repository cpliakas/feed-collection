<?php

/**
 * Feed Collection
 *
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt
 */

namespace Search\Collection\Feed;

use Search\Collection\SearchCollectionAbstract;
use Search\Collection\SearchCollectionQueue;
use Search\Index\SearchIndexDocument;

/**
 * A search collection for RSS / Atom feeds.
 */
class FeedCollection extends SearchCollectionAbstract
{
    /**
     * The feed being parsed.
     *
     * @var \SimplePie
     */
    protected $_feed;

    /**
     * Implements Search::Collection::SearchCollectionAbstract::init().
     *
     * Instantiates a SimplePie object, sets the feed URL if the "url" option
     * was passed via the constructor.
     */
    public function init()
    {
        $this->_feed = new \SimplePie();
        if ($url = $this->getOption('url')) {
            $this->_feed->set_feed_url($url);
        }
    }

    /**
     * Implements Search::Collection::SearchCollectionAbstract::getQueue().
     *
     * @todo Better error handling for null === $items;
     */
    public function getQueue($limit = SearchCollectionQueue::NO_LIMIT)
    {
        $this->_feed->init();
        $items = (array) $this->_feed->get_items();
        return new SearchCollectionQueue($items);
    }

    /**
     * Returns the SimplePie object.
     *
     * @return \SimplePie
     */
    public function getFeed()
    {
        return $this->_feed;
    }

    /**
     * Helper function to set a feed URL.
     *
     * @param string|array $url
     *   A URL or array of URLs.
     *
     * @return FeedCollection
     */
    public function setFeedUrl($url)
    {
        $this->_feed->set_feed_url($url);
        return $this;
    }

    /**
     * Implements Search::Collection::SearchCollectionAbstract::buildDocument().
     *
     * @param SearchIndexDocument $document
     * @param \SimplePie_Item $data
     */
    public function buildDocument(SearchIndexDocument $document, $data)
    {
        $document->id = $data->get_id();
        $document->title = $data->get_title();
        $document->authors = $data->get_authors();
    }
}
