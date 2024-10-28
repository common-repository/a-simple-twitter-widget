<?php

/**
 *	Plugin Name: Twidget
 *	Description: Display twitter feed in a widget.
 *	Version: 0.5.2
 *	Author: Florian Girardey
 *	Author URI: http://www.florian.girardey.net
 *	License: MIT
*/

define('VERSION', '0.5.2');
define('PLUGIN_URL', plugin_dir_url(__FILE__));

// Get TwitterOAuth library
require_once('lib/twitteroauth.php');

// Register the Widget twidget
add_action('widgets_init','twidget_init');
function twidget_init() {
	register_widget('twidget_widget');
}

/**
* twidget_widget
*
* @uses     WP_widget
*
* @category Widget
* @package  Twidget
* @author   GIRARDEY Florian <florian@girardey.net>
* @license  MIT
* @link     http://www.florian.girardey.net
*/
class twidget_widget extends WP_widget
{

    /**
     * Init the widget in WordPress
     * 
     * @access public
     * 
     * @since 0.1
     *
     * @return mixed Value.
     */
	public function twidget_widget() {

		$options = array(
			'classname'		=> 'twidget_widget',
			'description'	=> __('Display your twitter feed.', 'twidget')
		);
		$this->WP_widget('twidget', 'Twidget', $options);

	}

    /**
     * Generate HTML code which is displayed in the front-end.
     * 
     * @param mixed $args     Default arguments of the widget.
     * @param mixed $instance The instance of the widget.
     *
     * @access public
     * 
     * @since 0.1
     *
     * @return string Value.
     */
	public function widget($args, $instance) {

		// Register the widget id in a variable class to avoid troubleshoots
		$this->id = $args['widget_id'];

		// Register Twidget stylesheets and scripts
		wp_register_style( 'twidget', plugins_url('css/twidget.css', __FILE__), array(), VERSION, null );
		wp_enqueue_style( 'twidget' );

		$tweets = $this->getTweets($instance);

		echo $args['before_widget'];
		echo $args['before_title'] . $instance['title'] . $args['after_title'];

		?>
			<div class="twidget_feed">
				<?php foreach ($tweets as $k => $tweet): ?>
					<?php 
						$txt = $tweet->text;
						$txt = $this->encode_tweet($txt);
						$txt = $this->hyperlinks($txt);
						$txt = $this->twitter_users($txt);
						$txt = utf8_encode($txt);

						// var_dump($this);
					?>
					<div class="twidget_tweet">
						<div class="tweet-header">
							<?php if($this->profile_name) : ?>
								<a href="<?= $this->author_tweet_url($tweet) ?>">
									<?php if($this->profile_image) : ?>
										<img class="avatar" src="<?= $this->author_tweet_image_url($tweet) ?>" alt="<?= $this->author_tweet_fullname($tweet) ?>">
									<?php endif; ?>
									<strong class="author-fullname"><?= $this->author_tweet_fullname($tweet) ?></strong>
									<span class="author-username">@<?= $this->author_tweet_username($tweet); ?></span>
								</a>
							<?php endif; ?>
							<span class="date-create"><?= $this->date_tweet($tweet); ?></span>
						</div>
						<p><?= $txt ?></p>
					</div>
				<?php endforeach ?>
			</ul>
		<?php
		echo $args['after_widget'];
	}

    /**
     * Generate the form which is display in the back-end.
     * 
     * @param mixed $instance Description.
     *
     * @access public
     * 
     * @since 0.1
     *
     * @return string Value.
     */
	public function form($instance) {

		$default = array(
			'title' => 'Twidget'
		);

		$instance = wp_parse_args($instance, $default);

		?>
			<p>
				<label for="<?= $this->get_field_id('title') ?>"><?= __('Title', 'twidget'); ?> :</label>
				<input value="<?= $instance['title'] ?>" class="widefat" type="text" name="<?= $this->get_field_name('title') ?>" id="<?= $this->get_field_id('title') ?>" />
			</p>
			<p>
				<label for="<?= $this->get_field_id('consumer_key') ?>"><?= __('Consumer Key', 'twidget'); ?> :</label>
				<input value="<?= $instance['consumer_key'] ?>" class="widefat" type="text" name="<?= $this->get_field_name('consumer_key') ?>" id="<?= $this->get_field_id('consumer_key') ?>" />
			</p>
			<p>
				<label for="<?= $this->get_field_id('consumer_secret') ?>"><?= __('Consumer Secret', 'twidget'); ?> :</label>
				<input value="<?= $instance['consumer_secret'] ?>" class="widefat" type="text" name="<?= $this->get_field_name('consumer_secret') ?>" id="<?= $this->get_field_id('consumer_secret') ?>" />
			</p>
			<p>
				<label for="<?= $this->get_field_id('access_token') ?>"><?= __('Access Token', 'twidget'); ?> :</label>
				<input value="<?= $instance['access_token'] ?>" class="widefat" type="text" name="<?= $this->get_field_name('access_token') ?>" id="<?= $this->get_field_id('access_token') ?>" />
			</p>
			<p>
				<label for="<?= $this->get_field_id('access_token_secret') ?>"><?= __('Access Token Secret', 'twidget'); ?> :</label>
				<input value="<?= $instance['access_token_secret'] ?>" class="widefat" type="text" name="<?= $this->get_field_name('access_token_secret') ?>" id="<?= $this->get_field_id('access_token_secret') ?>" />
			</p>
			<p>
				<label for="<?= $this->get_field_id('count') ?>"><?= __('Number of Tweets', 'twidget'); ?> :</label>
				<select name="<?= $this->get_field_name('count') ?>" id="<?= $this->get_field_id('count') ?>">
					<?php
						// Generate 5 options
						for($i=1;$i<6;$i++) {
							if($instance['count'] == $i) echo '<option selected="selected" value="'.$i.'">'.$i.'</option>';
							else echo '<option value="'.$i.'">'.$i.'</option>';
						}
					?>
				</select>
			</p>
			<p>
				<input type="checkbox" <?php if($instance['profile_name']) echo 'checked=checked'; ?> id="<?= $this->get_field_id('profile_name') ?>" name="<?= $this->get_field_name('profile_name') ?>" value="true" />
				<label for="<?= $this->get_field_id('profile_name') ?>"><?= __('Display profile name ?', 'twidget'); ?></label>
				<br />
				<small><?php _e('If you turn off this option, the profile image will not appear.', 'twidget'); ?></small>
			</p>
			<p>
				<input type="checkbox" <?php if($instance['profile_image']) echo 'checked=checked'; ?> id="<?= $this->get_field_id('profile_image') ?>" name="<?= $this->get_field_name('profile_image') ?>" value="true" />
				<label for="<?= $this->get_field_id('profile_image') ?>"><?= __('Display profile image ?', 'twidget'); ?></label>
			</p>
		<?php
	}

    /**
     * Update the database with the form values.
     * If a new value is an empty string, the value is unset.
     * Reset the cache to display the correct number of tweets
     * 
     * @param array $new The new array of widget's params.
     * @param array $old The previous array of widget's params.
     *
     * @access public
     * 
     * @since 0.1
     *
     * @return mixed Value.
     */
	public function update($new, $old) {
		foreach ($new as $key => $value) { if(empty($value)) unset($new[$key]); }
		delete_transient($this->id.'-tweets');
		return $new;
	}

    /**
     * Get tweets from registered API information.
     * Set a cache for tweets with the Transient API, the twitter feed is kept 60s
     * 
     * @param mixed $instance Description.
     *
     * @access private
     * 
     * @uses TwitterOAuth library, Transient API from Wordpress
     * 
     * @since 0.1
     *
     * @return mixed Value.
     */
	private function getTweets($instance) {

		$cache = get_transient($this->id.'-tweets');

		$defaults = array(
			'consumer_key'			=> 'TNRQcXyZP3enOAT14vaoA',
			'consumer_secret'		=> 'aYwpSWoYkfL8MKnmlgxMHSQH4DpJR3MTPt32FVdaLg',
			'access_token'			=> '362451644-jybYhIL5stX59UXytmyALugRhXZICtWHsCj8ahSY',
			'access_token_secret'	=> 'zJPCCkj8dCd6HujuVQqaIaOf0n4ZtW6H2zARLeuz78k',
			'count'					=> 3,
			'profile_name'			=> false,
			'profile_image'			=> false
		);

		$instance = wp_parse_args($instance, $defaults);

		$this->profile_name = (bool) $instance['profile_name'];
		$this->profile_image = (bool) $instance['profile_image'];

		if(!$cache){

			$connection = new TwitterOAuth(
				$instance['consumer_key'],
				$instance['consumer_secret'],
				$instance['access_token'],
				$instance['access_token_secret']
			);

			$tweets = $connection->get('statuses/user_timeline', array('count' => $instance['count']));
			set_transient($this->id.'-tweets', serialize($tweets), 60);
		}
		else $tweets = unserialize($cache);

		return $tweets;
	}

    /**
     * Hyperlinks parser for tweets
     * 
     * @param string $text tweet.
     *
     * @access private
     * 
     * @since 0.2
     *
     * @return string HTML tweets.
     */
	private function hyperlinks($text) {
		$text = preg_replace( '@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@', '<a href="$1" class="twidget_link">$1</a>', $text);
	    $text = preg_replace( '/([\s|\|"])+#(\w+)/', '$1<a href="http://twitter.com/search?q=%23$2" class="twidget_hashtag">#$2</a>', $text);
	    return $text;
	}

    /**
     * Twitter User link parser
     * 
     * @param string $text tweet.
     *
     * @access private
     * 
     * @since 0.2
     *
     * @return string HTML tweets.
     */
	private function twitter_users($text) {
		$text = preg_replace('/@(\w+)/', '<a href="http://twitter.com/$1" class="twidget_user">@$1</a>', $text);
		return $text;
	}

    /**
     * Encode single quotes in tweets
     * 
     * @param string $text tweet.
     *
     * @access private
     * 
     * @since 0.2
     *
     * @return string HTML tweets encoded.
     */
	private function encode_tweet($text) {
		$text = mb_convert_encoding( $text, "HTML-ENTITIES", "UTF-8");
		return $text;
	}

    /**
     * Return a user-friendly created_at for a specified tweet
     * 
     * @param mixed $tweet Only one tweet.
     *
     * @access private
     * 
     * @since 0.4
     *
     * @return string Value.
     */
	private function date_tweet($tweet) {
		$time = strtotime($tweet->created_at);
		$diff = time() - $time;
		$mins = $diff / 60;
		$hours = $mins / 60;
		$days = $hours / 24;

		if($diff < 60) { $toDisplay = $diff; $alias = __('s','twidget'); }
		else if($mins < 60) { $toDisplay = $mins; $alias = __('min','twidget'); }
		else if($hours < 24) { $toDisplay = $hours; $alias = __('h','twidget'); }
		else if($days < 7) { $toDisplay = $days; $alias = __('d','twidget'); }
		$toDisplay = floor($toDisplay);
		return $toDisplay.' '.$alias;
	}

    /**
     * Return the @Name of the author of the tweet
     * 
     * @param mixed $tweet The tweet.
     *
     * @access private
     * 
     * @since 0.4
     *
     * @return string Value.
     */
	private function author_tweet_username($tweet){
		return $tweet->user->screen_name;
	}

    /**
     * Return the real name of the author of the tweet
     * 
     * @param mixed $tweet The tweet.
     *
     * @access private
     * 
     * @since 0.4
     *
     * @return string Value.
     */
	private function author_tweet_fullname($tweet){
		return $tweet->user->name;
	}

	/**
     * Return the real name of the author of the tweet
     * 
     * @param mixed $tweet The tweet.
     *
     * @access private
     * 
     * @since 0.4
     *
     * @return string Value.
     */
	private function author_tweet_url($tweet){
		return 'https://twitter.com/' . $tweet->user->screen_name;
	}

	/**
     * Return the real name of the author of the tweet
     * 
     * @param mixed $tweet The tweet.
     *
     * @access private
     * 
     * @since 0.4
     *
     * @return string Value.
     */
	private function author_tweet_image_url($tweet){
		return $tweet->user->profile_image_url;
	}

}
