<?php
/**
 * MarkLogic WordPress Search
 *
 * @category    WordPress
 * @package     MarkLogicWordPressSearch
 * @license     http://opensource.org/licenses/GPL-2.0 GPL-2.0+
 * @copyright   2013 MarkLogic Corporation
 */

namespace MarkLogic\WordPressSearch;

use MarkLogic\MLPHP;

/**
 * Search processor
 *
 * @since   1.0
 * @author  Eric Bloch <eric.bloch@gmail.com>
 */
class Searcher {

    public function init() 
    {
		add_action('get_search_form', array($this, 'search_form'));
		add_action('pre_get_posts', array($this, 'do_search'));
		add_action('the_posts', array($this, 'process_search'));

        // Can I do the below on a post instead of its pieces?
		// add_filter('get_search_form', array($this, 'process_post'));

		// add_filter('the_title', array($this, 'highlight_text'));
		// add_filter('the_author', array($this, 'highlight_text'));
    	add_filter('the_content', array($this, 'highlight_text'));
		// add_filter('the_excerpt', array($this, 'highlight_text'));

        // The ones here don't seem to be working with the marklogic-v2 theme, which uses a deprecated get_the_author() call
		// add_filter('the_author_display_name', array($this, 'highlight_text'));
		// add_filter('the_author_first_name', array($this, 'highlight_text'));
		// add_filter('the_author_last_name', array($this, 'highlight_text'));

        add_filter('get_the_excerpt', array($this, 'do_excerpt'));
    }

    function search_form($f) {
        wp_enqueue_style(
            'marklogic_search',
            plugins_url( 'wordpress-marklogic-search/css/search.css' ) ,
            array(), '1.0'
        );

        wp_enqueue_script(
            'marklogic_search',
            plugins_url( 'wordpress-marklogic-search/js/form.js' ) ,
            array('jquery'), '1.0', true
        );
    }

    function highlight_text($t) {
        global $post;
        $plugin = Plugin::instance();
        if ($plugin->option('enabled')) {
            if (class_exists("\WP_Session")) {
                $wp_session = \WP_Session::get_instance(); 
                Plugin::debug("SESSION " . serialize($wp_session));
    
                if (isset($wp_session['wms_s'])) {
                    $t = Searcher::highlight($wp_session['wms_s'], "text/plain", $t);
                }
            } 
        }
        return $t;
    }

    // Replace excerpt in search results with snippet.  For now, just pre-p
    function do_excerpt($param) {
        global $post;
        $plugin = Plugin::instance();

        Plugin::debug("Excerpt for post: " . $post->ID);

        if (is_search() && $plugin->option('enabled') && isset($this->snippets) && !empty($this->snippets[$post->ID])) {
            
            $p = '';
            foreach ($this->snippets[$post->ID] as $snippet) {
                $elt = substr(strrchr($snippet->getPath(), "/"), 1);

                $hasName = isset(Searcher::$constraints[$elt]);

                if ($hasName && $elt != "content-tidy") {
                    // $name = Searcher::$constraints[$elt];
                    // if ($name) {
                        // $p .= "<b>" . $name . ":&#160;</b>" . $snippet->getContent() . "<br/>";
                    // }
                } else {
                    $munged = $string = preg_replace('/\s+/', ' ', trim($snippet->getContent())) . "<br/>"; // ripping out newlines
                    $p .= $munged;
                }
            }
            return $p ; // . '<br/>' ; // . $param;

        } else {
            return $param;
        }
    }

	function do_search($wp_query){
		$this->searched = false;

        $plugin = Plugin::instance();

		if(!$wp_query->is_main_query() || !is_search() || is_admin() || !$plugin->option('enabled')){
			return;
		}

        // XXX wp seems to be putting slashes in front of quotes.  evil.
		$search = $wp_query->query_vars['s'];

		$this->page = $wp_query->query_vars['paged'] > 0 ? $wp_query->query_vars['paged'] - 1 : 0;

		if(!isset($wp_query->query_vars['posts_per_page'])){
			$wp_query->query_vars['posts_per_page'] = get_option('posts_per_page'); // XXX FIXME - why is this option needed?
		}
        Plugin::debug("posts_per_page " . $wp_query->query_vars['posts_per_page']);

		$results = Searcher::query($search, $this->page, $wp_query->query_vars['posts_per_page'], $wp_query->query_vars);

		if ($results == null) {
			return null;
		}

		$this->searched = true;	
		$this->total = $results['total'];
		$this->scores = $results['scores'];
		$this->snippets = $results['snippets'];

        if (class_exists("\WP_Session")) {
            $wp_session = \WP_Session::get_instance(); 
            $wp_session['wms_s'] = $search;
            Plugin::debug("wms_s " . $search);
        }
		
		$wp_query->query_vars['s'] = '';	

		if ($results['total'] < 1){
			return null;
		}

		$wp_query->query_vars['post__in'] = $results['ids'];

        Plugin::debug("IDs " . implode(" ", $results['ids']));
        
		$wp_query->query_vars['paged'] = 1;
		$wp_query->facets = $results['facets'];
	}

	function process_search($posts){
		global $wp_query;

        Plugin::debug('count($posts) ' . count($posts));

		if($this->searched){
			$this->searched = false;

			$wp_query->max_num_pages = ceil( $this->total / $wp_query->query_vars['posts_per_page'] );
			$wp_query->found_posts = $this->total;
			$wp_query->query_vars['paged'] = $this->page + 1;
			$wp_query->query_vars['s'] = $_GET['s'];

            if ($this->total > 0) 
			    usort($posts, array(&$this, 'sort_posts'));
            else 
                array_splice($posts, 0, count($posts));
		}

		return $posts;
	}

	protected function sort_posts($a, $b){
        Plugin::debug('sort '  . $a->ID . ' ' . $b->ID);
        Plugin::debug('sort scores '  . $this->scores[$a->ID] . ' ' . $this->scores[$b->ID]);
        $x = $this->scores[$a->ID];
        $y = $this->scores[$b->ID];
        if ($x == $y) return 0;
		return ($x < $y) ? -1 : 1; 
	}

    static public $constraints = array(
        'content-tidy' => "content",
        'title'        => "title",
        'name'         => "url",
        "display_name" => "author"
    );
    
    public function query($search, $pageIndex, $size, $facets = array()) {

        $options = new MLPHP\Options(Plugin::client()->getRestClient());

        foreach (self::$constraints as $elt => $name) {
            $constraint = new MLPHP\WordConstraint($name, $elt, "", null, null);
            $constraint->setTermOptions(array(
                'wildcarded'
            ));
            $options->addConstraint($constraint);
        }

        // XXX Field should be named content01
        $field_constraint = new MLPHP\FieldWordConstraint("default", "content01");

        $term = new MLPHP\Term('all-results');
        $term->setTermOptions(array(
            'wildcarded'
        ));

        // $term->setDefault($content_constraint);  /* Can use this when you don't have the DB field named content 'content01' defined */
        $term->setDefault($field_constraint); 
        $options->setTerm($term);


        try {
            $options->write("wms");
            Plugin::debug("Wrote wms options");
        } catch (\Exception $ex) {
            Plugin::error($ex);
            return null;
        }
            
        $query = new MLPHP\Search(Plugin::client()->getRestClient(), $pageIndex * $size, $size);

        //Possibility to modify the query after it was built
        \apply_filters('mlphp_query', $query);

        $results = null;

        try {
            $results = $query->retrieve($search, array(
                'options' => 'wms' 
            ));
        } catch (\Exception $ex) {
            Plugin::error($ex);
            return null;
        }

        
        $ids = array();
        $scores = array();
        $snippets = array();
        $facets = array();

        foreach ($results->getResults() as $result) {
            $id = DocumentBuilder::uri_to_id($result->getURI());
            $ids[] = $id;
            $scores[$id] = $result->getScore();
            $snippets[$id] = array();
            foreach ($result->getMatches() as $match) {
                $snippets[$id][] = $match;
            }
        }

        Plugin::debug("Total : " . $results->getTotal());

        $val = array(
            'total' => $results->getTotal(),
            'snippets' => $snippets,
            'scores' => $scores,
            'facets' => $facets,
            'ids' => $ids
        );

        return \apply_filters('marklogic_search_results', $val, $results);
    }

    public function highlight($search, $contentType, $content) {
        $query = new MLPHP\Search(Plugin::client()->getRestClient());
        return $query->highlight($content, $contentType, "hit", $search);
    }

}

