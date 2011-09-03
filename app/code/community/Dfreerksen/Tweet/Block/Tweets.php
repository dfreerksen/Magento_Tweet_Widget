<?php

class Dfreerksen_Tweet_Block_Tweets extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface {

	const TWEET_CACHE_KEY = 'store_%s_tweets_%s_count_%s';   // store_STOREID_tweets_USERNAME_count_COUNT
	const TWEET_CACHE_TAG = 'tweets_%s_count_%s';            // tweets_USERNAME_count_COUNT

	protected $_serializer = null;

	/**
	 * Initialization
	 */
	protected function _construct()
	{
		$this->_serializer = new Varien_Object();

		parent::_construct();
	}

	/**
	 * Produces twitter html
	 *
	 * @return  string
	 */
    protected function _toHtml()
    {
		// Username to pull the tweets for
		$username = $this->getData('username');

		// Cache lifetime
		$lifetime = $this->getData('lifetime');

		// Count
		$count = $this->getData('count');

		// Cache ID
		$cache_id = sprintf(self::TWEET_CACHE_KEY, Mage::app()->getStore()->getId(), $username, $count);

		if ( ! $username)
		{
			return '';
		}

		// Load the cached tweets
		$tweets = Mage::app()->loadCache($cache_id);

	    // No tweets were cached. Load them in
		if ( ! $tweets)
		{
			// Empty array to put the returned tweets in
			$tweets = array();

			// Pull the tweets from Twitter
			$tweet = simplexml_load_file('http://twitter.com/statuses/user_timeline/'.$username.'.xml?count='.$count);

			// Loop over the tweets and create links
			foreach($tweet->status as $key)
			{
				$data = array(
					'text' => $this->_transform($key->text),
					'created_at' => strtotime($key->created_at),
				);

			    $tweets[] = $data;
			}

			// Serialize the results so it can be cached
			$tweets = serialize($tweets);

			// Give the cache a tag
			$tag = sprintf(self::TWEET_CACHE_TAG, $username, $count);
			Mage::app()->saveCache($tweets, $cache_id, array($tag), $lifetime);
		}

		// Un-serialize the tweets so it can be sent to the page as an array
		$tweets = unserialize($tweets);

		// Assign the tweets to a variable (to be used by the template file)
		$this->assign('tweets', $tweets);

		// And... we're done
		return parent::_toHtml();
    }

	/**
	 * Cache lifetime
	 *
	 * @return  int
	 */
	public function getLifetime()
	{
		return $this->getData('lifetime');
	}

	/**
	 * Tweets to return
	 *
	 * @return  int
	 */
	public function getCount()
	{
		return $this->getData('count');
	}

	/**
	 * Twitter username
	 *
	 * @return  string
	 */
	public function getUsername()
	{
		return $this->getData('username');
	}

	/**
	 * Class to @ links
	 *
	 * @return  string
	 */
	public function getAtClass()
	{
		return $this->getData('at_class');
	}

	/**
	 * Class to # links
	 *
	 * @return  string
	 */
	public function getPoundClass()
	{
		return $this->getData('pound_class');
	}

	/**
	 * Wrapper CSS class
	 *
	 * @return  string
	 */
	public function getCssWrapperClass()
	{
		return $this->getData('css_wrapper_class');
	}

	/**
	 * CSS class
	 *
	 * @return  string
	 */
	public function getCssClass()
	{
		return $this->getData('css_class');
	}

	/**
	 * Add links to tweets
	 *
	 * @param   string  $ret
	 * @return  string
	 */
	private function _transform($ret)
	{
		$at_class =  $this->getAtClass();
		$pound_class =  $this->getPoundClass();

		// Query
		$ret = preg_replace("#(^|[\n ])\#([^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://www.twitter.com/search?q=\\2\" rel=\"nofollow\">#\\2</a>'", $ret);

		// @ link
		$ret = preg_replace("#(^|[\n ])@([^ \"\t\n\r<]*)#ise", "'\\1<a class=\"".$at_class."\" href=\"http://www.twitter.com/\\2\" >@\\2</a>'", $ret);

		// # link
		$ret = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t<]*)#ise", "'\\1<a class=\"".$pound_class."\" href=\"\\2\" >\\2</a>'", $ret);

		// General link
		$ret = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://\\2\" >\\2</a>'", $ret);

		return $ret;
	}

}