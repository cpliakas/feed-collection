<?php

/**
 * Feed collection for the Search Framework library.
 *
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt
 */

namespace Search\Collection\Feed;

use Search\Framework\SearchCollectionAbstract;
use Search\Framework\SearchQueueMessage;
use Search\Framework\SearchIndexDocument;

/**
 * A search collection for RSS / Atom feeds.
 */
class FeedCollection extends SearchCollectionAbstract
{

    protected $_type = 'feeds';

    protected static $_configBasename = 'feed';

    protected static $_defaultLimit = 50;

    protected static $_defaultTimeout = 30;

    /**
     * The feed being parsed.
     *
     * @var \SimplePie
     */
    protected $_feed;

    /**
     * The consumed feed items keyed by its unique identifier.
     *
     * @var array
     */
    protected $_scheduledItems;

    /**
     * Implements SearchCollectionAbstract::init().
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
     * Fetches the specified number of feed items from the resource.
     *
     * This is a wrapper around \SimplePie::get_items(), except that it also
     * keys the array by each feed's unique identifier. This method can also be
     * used to warm the cache in parallel indexing configurations.
     *
     * @param int $limit
     *   The maximum number of feeds to process, defaults to no limit which will
     *   pull whatever the resource has published.
     *
     * @return array
     *   An array of \SimplePie_Item objects keyed by the feed item's unique
     *   identifier.
     */
    public function fetchFeedItems($limit = self::NO_LIMIT)
    {
        $end = ($limit != self::NO_LIMIT) ? $limit : 0;

        // Get the array of feed items.
        $this->_feed->init();
        $items = $this->_feed->get_items(0, $end);
        if (null === $items) {
            // @todo Log the error?
            $items = array();
        }

        // Key the array by the feed's unique ID.
        foreach ($items as $item) {
            $item_id = $item->get_id();
            $items[$item_id] = $item;
            // @todo Should we unset the original key?
        }

        return $items;
    }

    /**
     * Implements SearchCollectionAbstract::fetchScheduledItems().
     *
     * This method simply fetches whatever is published by the resource.
     */
    public function fetchScheduledItems()
    {
        $limit = $this->getLimit();
        $this->_scheduledItems = $this->fetchFeedItems($limit);
        return new \ArrayIterator($this->_scheduledItems);
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
     * Implements SearchCollectionAbstract::buildQueueMessage().
     *
     * @param SearchQueueMessage $message
     * @param \SimplePie_Item $item
     */
    public function buildQueueMessage(SearchQueueMessage $message, $item)
    {
        $item_id = $item->get_id();
        $message->setBody($item_id);
    }

    /**
     * Implements SearchCollectionAbstract::loadSourceData().
     */
    public function loadSourceData(SearchQueueMessage $message)
    {
        $item_id = $message->getBody();
        if (isset($this->_scheduledItems[$item_id])) {
            return $this->_scheduledItems[$item_id];
        }

        // @todo Handle the error. This is only an issue in parallel indexing
        // configurations.
        return false;
    }

    /**
     * Implements SearchCollectionAbstract::buildDocument().
     *
     * @param SearchIndexDocument $document
     * @param \SimplePie_Item $data
     */
    public function buildDocument(SearchIndexDocument $document, $data)
    {
        $document->source = $this->_feed->get_title();
        $document->subject = $this->_feed->get_description();

        $document->title = $data->get_title();
        $document->link = $data->get_link();
        $document->description = $data->get_description();
        $document->creator = (array) $data->get_author();
        $document->date = $data->get_date();

        // PHP properties cannot have dashes (-), and the fields below have
        // dashes in the field name.

        $document->source_link = $this->_feed->get_link();
        $document->getField('source_link')->setName('source-link');

        $document->item_subject = $this->_feed->get_link();
        $document->getField('item_subject')->setName('item-subject');
    }
}
