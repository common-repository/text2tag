<?php
/*
Plugin Name: Text2Tag
Plugin URI: http://joost.reuzel.nl/plugins
Description: Text2Tag converts all words from a post to tags, making the taglist reflecting the real content of the site. A list of stopwords can be entered to ignore common words.
Version: 1.2
Author: Joost Reuzel
Author URI: http://joost.reuzel.nl/
*/

/*
	This file is part of Text2Tag.

    'Text2Tag' is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    'Text2Tag' is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with 'Text2Tag'.  If not, see <http://www.gnu.org/licenses/>.
	
	Note that this software makes use of other libraries that are published under their own
	license. This program in no way tends to violate the licenses under which these 
	libraries are published.
	
*/

if(!class_exists('Text2Tag'))
{
	class Text2Tag
	{
		function __construct()
		{
			//add new taxonomy during init
			add_action("init", array(&$this, "onInit"));
			
			//add options panel
			add_action("admin_menu", array(&$this, "onAdminMenu"));
			add_action("admin_init", array(&$this, "onAdminInit"));
						
			//set post save action
			add_action("save_post", array(&$this, "onSavePost"), 10, 2);
			add_action("deleted_post", array(&$this, "onDeletePost"), 10, 2);
			
			//(de)activation hook
			register_activation_hook(__FILE__, array(&$this, 'onActivate'));
			register_deactivation_hook(__FILE__, array(&$this, 'onDeActivate'));
		}
		
		/**
		 * fetches the current taxonomy
		 */
		function getTaxonomy()
		{
			return get_option("text2tag_taxonomy", "words");
		}
		
		/**
		 * fetches the current list of stopwords
		 * @return array list of stopwords
		 */
		function getStopWords()
		{
			$wrdString = get_option("text2tag_stopwords");
			return explode(" ", $wrdString);
		}
		
		function noNumbers()
		{
			$noNumbers = get_option("text2tag_nonumbers", "1");
			return !empty($noNumbers);
		}
		
		/**
		 * adds the options page to the admin menu
		 * @return unknown_type
		 */
		function onAdminMenu()
		{
			return add_options_page('Text2Tag', 'Text2Tag', 8, basename(__FILE__), array(&$this, 'renderOptionPanel'));
		}
		
		/**
		 * renders the option panel
		 * @return unknown_type
		 */
		function renderOptionPanel()
		{
			$dir = plugin_dir_path(__FILE__);
			include $dir . "OptionPage.php";
		}
		
		/**
		 * registers the new "word" taxonomy
		 * @return unknown_type
		 */
		function onInit()
		{
			if(!is_taxonomy("words") && ($this->getTaxonomy() == "words"))
				register_taxonomy('words', 'post', array('hierarchical' => false, 'update_count_callback' => '_update_post_term_count', 'label' => __('Words'))) ;
		}
		
		/**
		 * registers the text2tag options. Attaches filters to handle changes to these values
		 * @return unknown_type
		 */
		function onAdminInit()
		{
			register_setting('text2tag', 'text2tag_stopwords', array(&$this, "stopWordsFilter"));
			add_action('update_option_text2tag_stopwords', array(&$this, "onStopWordsUpdated"), 10, 2);
			
			register_setting('text2tag', 'text2tag_taxonomy');
			add_action('update_option_text2tag_taxonomy', array(&$this, "onTaxonomyUpdated"), 10, 2);
			
			register_setting('text2tag', 'text2tag_nonumbers');
			add_action('update_option_text2tag_nonumbers', array(&$this, "onNoNumbersUpdated"), 10, 2);
		}
		
		/**
		 * clean the words from the option input (remove commas, etc.)
		 * @param $stopwords
		 * @return unknown_type
		 */
		function stopWordsFilter($stopwords)
		{
			//clean and grab the words
			$wrds = $this->textToWords($stopwords);
					
			//return cleaned wordlist
			return implode(" ", $wrds);
		}
		
		/**
		 * iterates over all posts and fills the taxonomy with the words
		 * @param $oldValue
		 * @param $newValue
		 * @return unknown_type
		 */
		function onTaxonomyUpdated($oldValue, $newValue)
		{
			//wipe all words from the old taxonomy
			$this->cleanTaxonomy($oldValue, true);
			
			//if new value is words, init...
			if($newValue = "words")
				$this->onInit();
				
			//fill the new taxonomy
			$this->updateAllPosts();
		}
		
		function onNoNumbersUpdated($oldValue, $newValue)
		{
			if(!empty($newValue))
			{
				//get current taxonomy
				$taxonomy = $this->getTaxonomy();	
				
				//get all terms sorted asc by count
				$args = array('orderby' => 'count', 'order' => 'ASC', 'hide_empty' => false, 'fields' => 'all');
				$allTerms = get_terms($taxonomy, $args);
				
				//iterate over each term
				foreach($allTerms as $term)
				{
					//remove if term is a number
					if(is_numeric($term->name))
						wp_delete_term($term->term_id, $taxonomy);
				}
			}
			else
				$this->updateAllPosts();
		}
		
		/**
		 * removes unused terms from the taxonomy
		 * @param $taxonomy the name of the taxonomy
		 * @param $all set to true to remove all terms from the taxonomy
		 */
		function cleanTaxonomy($taxonomy = '', $all=false)
		{
			//get default taxonomy if none set
			if(empty($taxonomy))
				$taxonomy = $this->getTaxonomy();
					
			//get all terms sorted asc by count
			$args = array('orderby' => 'count', 'order' => 'ASC', 'hide_empty' => false, 'fields' => 'all');
			$allTerms = get_terms($taxonomy, $args);
			
			//iterate over each term
			foreach($allTerms as $term)
			{
				//remove if all must be removed or term count=0
				if($all || $term->count==0)
					wp_delete_term($term->term_id, $taxonomy);
				else
					break; //break, rest of terms has count>0
			}
		}
			
		/**
		 * run when list of stopwords is updated
		 * @param $oldList old list of stopwords
		 * @param $newList new list of stopwords
		 * @return unknown_type
		 */
		function onStopWordsUpdated($oldList, $newList)
		{
			$old = explode(" ", $oldList);
			$new = explode(" ", $newList);
			
			$addList = array_diff($new, $old); //list of new stopwords
			$removeList = array_diff($old, $new); //list of removed stopwords
			
			//remove all new stopwords in the list from the taxonomy
			$tax = $this->getTaxonomy();
			foreach($addList as $wrd)
			{
				if($term = is_term($wrd, $tax))
					wp_delete_term($term['term_id'], $tax);
			}
			
			//if words were removed from stopword list, update tags of all posts (to get that word back into taxonomy if used)
			if(count($removeList)>0)
			{
				$this->updateAllPosts();
			}
		}
		
		/**
		 * called when a post is saved. Updates the tags for this post
		 * @param $postId id of the post
		 * @param $post the post itself
		 * @return unknown_type
		 */
		function onSavePost($postId, $post)
		{
			$this->updatePost($post);
		}
		
		
		/**
		 * called when a post is deleted. Cleans unused tags from the DB.
		 * @param $postId
		 * @return unknown_type
		 */
		function onDeletePost($postId)
		{
			//remove all relations between this post and the corresponding terms in the words taxonomy
			$taxonomy = $this->getTaxonomy();
			if($taxonomy=="words")
				wp_delete_object_term_relationships($postid, array('words'));
			
			$this->cleanTaxonomy();
		}
		
		/**
		 * called when plugin is activated. Creates the taxonomy, and updates all posts.
		 * @return unknown_type
		 */
		function onActivate()
		{
			//options
			add_option("text2tag_taxonomy", "words");
			add_option("text2tag_stopwords", "");
			add_option("text2tag_nonumbers", "1");
						
			//do all stuff to init this plugin
			$this->onInit();
			
			//update all posts
			$this->updateAllPosts();
		}
		
		/**
		 * removes the "words" taxonomy
		 * @return unknown_type
		 */
		function onDeActivate()
		{
			$this->cleanTaxonomy("words", true);

			delete_option("text2tag_stopwords");
			delete_option("text2tag_taxonomy");
			delete_option("text2tag_nonumbers");
		}
		
		/**
		 * updates all posts with the new taxonomy
		 */
		function updateAllPosts()
		{
			//set the words for the new taxonomy
			$posts = get_posts(array('numberposts'=>-1));
			foreach($posts as $post)
			{
				$this->updatePost($post);
			}
			
			//remove all unused tags
			$this->cleanTaxonomy();
		}
		
		/**
		 * updates the tags for the provided post
		 * @param $post
		 * @return unknown_type
		 */
		function updatePost($post)
		{
			//$post = (int) $post;
			//$post = get_post($post);
			
			$wrds = $this->textToWords($post->post_title . " " . $post->post_content);
			$wrds = array_diff($wrds, $this->getStopWords());
			wp_set_post_terms($post->ID, $wrds, $this->getTaxonomy(), false);
		}
		
		/**
		 * gets the list of words from a text (post or stopwords input field)
		 * @param $text
		 * @return unknown_type
		 */
		function textToWords($text)
		{
			$text = strip_tags($text); //remove html
			$text = strip_shortcodes($text); //remove shortcodes
			$text = html_entity_decode($text); //translate html chars to normal chars
			$text = wp_specialchars_decode($text); //translate some extra html
			$text = stripslashes($text); //get rid of extra escape slashes
			$wrds = preg_split("/[\s,.;!?:()\"]+/", $text, -1, PREG_SPLIT_NO_EMPTY); //spit string into words
			$wrds = array_map(create_function('$wrd', 'return trim($wrd, "\"\'-&^_");'), $wrds); //trim strange characters from words
			$wrds = array_map('strtolower', $wrds); //make lowercase
			$wrds = array_unique($wrds); //remove duplicates
			$wrds = array_filter($wrds); //remove empty strings
			if($this->noNumbers()) 
				$wrds = array_filter($wrds, create_function('$wrd', 'return !is_numeric($wrd);')); //remove numbers
			
			return $wrds;
		}
		
	}
}

//create the plugin (if not done so)
if(!isset($text2tag))
	$text2tag = new Text2Tag(); 