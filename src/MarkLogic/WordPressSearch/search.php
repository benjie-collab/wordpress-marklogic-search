<?php
namespace MarkLogic\WordPressSearch;

/* See http://codex.wordpress.org/Class_Reference/WP_Query */

class Search{
	var $searched = false;
	var $total = 0;
	var $scores = array();
    var $snippets = array();
	var $page = 1;

	function __construct(){
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
        if (Api::option('enabled')) {
            $wp_session = \WP_Session::get_instance(); 
            Api::logger()->debug("SESSION " . serialize($wp_session));

            if (isset($wp_session['wms_s'])) {
                $t = Searcher::highlight($wp_session['wms_s'], "text/plain", $t);
            }
        }
        return $t;
    }

    // Replace excerpt in search results with snippet.  For now, just pre-p
    function do_excerpt($param) {
        global $post;

        Api::logger()->debug("Excerpt for post: " . $post->ID);

        if (is_search() && Api::option('enabled') && isset($this->snippets) && !empty($this->snippets[$post->ID])) {
            
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

		if(!$wp_query->is_main_query() || !is_search() || is_admin() || !Api::option('enabled')){
			return;
		}

		$search = $wp_query->query_vars['s'];

		$this->page = $wp_query->query_vars['paged'] > 0 ? $wp_query->query_vars['paged'] - 1 : 0;

		if(!isset($wp_query->query_vars['posts_per_page'])){
			$wp_query->query_vars['posts_per_page'] = get_option('posts_per_page'); // XXX FIXME - why is this option needed?
		}
        Api::logger()->debug("posts_per_page " . $wp_query->query_vars['posts_per_page']);

		$results = Searcher::query($search, $this->page, $wp_query->query_vars['posts_per_page'], $wp_query->query_vars);

		if ($results == null) {
			return null;
		}

		$this->searched = true;	
		$this->total = $results['total'];
		$this->scores = $results['scores'];
		$this->snippets = $results['snippets'];

        $wp_session = \WP_Session::get_instance(); 
        $wp_session['wms_s'] = $search;
        Api::logger()->debug("wms_s " . $search);
		
		$wp_query->query_vars['s'] = '';	

		if ($results['total'] < 1){
			return null;
		}

		$wp_query->query_vars['post__in'] = $results['ids'];

        Api::logger()->debug("IDs " . implode(" ", $results['ids']));
        
		$wp_query->query_vars['paged'] = 1;
		$wp_query->facets = $results['facets'];
	}

	function process_search($posts){
		global $wp_query;

        Api::logger()->debug('count($posts) ' . count($posts));

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

	function sort_posts($a, $b){
        Api::logger()->debug('sort '  . $a->ID . ' ' . $b->ID);
        Api::logger()->debug('sort scores '  . $this->scores[$a->ID] . ' ' . $this->scores[$b->ID]);
        $x = $this->scores[$a->ID];
        $y = $this->scores[$b->ID];
        if ($x == $y) return 0;
		return ($x < $y) ? -1 : 1; 
	}
}

new Search();

