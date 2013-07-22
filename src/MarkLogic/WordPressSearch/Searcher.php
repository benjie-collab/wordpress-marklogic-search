<?php
namespace MarkLogic\WordPressSearch;

use MarkLogic\MLPHP;

class Searcher{

    static function esc($s) {
        return htmlentities($s, ENT_COMPAT, "UTF-8");
    }

	public function query($search, $pageIndex, $size, $facets = array()) {

		$bytype = null;

		foreach(Api::types() as $type){
			if($type == $search){
				$bytype = $search;
				$search = null;
			}
		}

        /*
		foreach(Api::taxonomies() as $tax){
			if($search){
				$score = Api::score('tax', $tax);

				if($score > 0){
					$shoulds[] = array('text' => array( $tax => array(
						'query' => $search,
						'boost' => $score
					)));
				}
			}

			self::facet($tax, $facets, 'term', $musts, $filters);
		}

		$args = array();

		$numeric = Api::option('numeric');

		foreach(Api::fields() as $field){
			if($search){
				$score = Api::score('field', $field);

				if($score > 0){
					$shoulds[] = array('text' => array($field => array(
						'query' => $search,
						'boost' => $score
					)));
				}
			}

			if(isset($numeric[$field]) && $numeric[$field]){
				$ranges = Api::ranges($field);

				if(count($ranges) > 0 ){
					self::facet($field, $facets, 'range', $musts, $filters, $ranges);
				}
			}
		}

		if(count($shoulds) > 0){
			$args['query']['bool']['should'] = $shoulds;
		}

		if(count($filters) > 0){
			$args['filter']['bool']['should'] = $filters;
		}

		if(count($musts) > 0){
			$args['query']['bool']['must'] = $musts;
		}

		foreach(Api::facets() as $facet){
			$args['facets'][$facet]['terms']['field'] = $facet;

			if(count($filters) > 0){
				foreach($filters as $filter){
					if(!$filter['term'][$facet]){
						$args['facets'][$facet]['facet_filter']['bool']['should'][] = $filter;
					}
				}
			}
		}
		
		$args = \apply_filters('es_query_args', $args);

		if($numeric) {
			foreach(array_keys($numeric) as $facet){
				$ranges = Api::ranges($facet);

				if(count($ranges) > 0 ){
					$args['facets'][$facet]['range'][$facet] = array_values($ranges);
					
					if(count($filters) > 0){
						foreach($filters as $filter){
							$args['facets'][$facet]['facet_filter']['bool']['should'][] = $filter;
						}
					}
				}
			}
		}

		$args = \apply_filters('es_query_args', $args);
        */

        $options = new MLPHP\Options(Api::client());
            $term = new MLPHP\Term('all-results');
            $term->setTermOptions(array('wildcarded'));
            $options->setTerm($term);

            $content_constraint = new MLPHP\WordConstraint("content", "content", "", null, null);
            $content_constraint->setTermOptions(array(
                'wildcarded'
            ));
            $options->addConstraint($content_constraint);

            $title_constraint = new MLPHP\WordConstraint("title", "title", "", null, null);
            $title_constraint->setTermOptions(array(
                'wildcarded'
            ));
            $options->addConstraint($title_constraint);

            $author_constraint = new MLPHP\WordConstraint("author", "display_name", "", null, null);
            $author_constraint->setTermOptions(array(
                'wildcarded'
            ));
            $options->addConstraint($author_constraint);

        try {
            $options->write("wms");
            Api::logger()->debug("Wrote wms options");
        } catch (\Exception $ex) {
            Api::logger()->error($ex);
            return null;
        }
            
        $query = new MLPHP\Search(Api::client(), $pageIndex * $size, $size);

        // For now, just search with in the content and title only

        $structure = '
            <query xmlns="http://marklogic.com/appservices/search">
                <or-query>
                    <word-constraint-query>
                        <constraint-name>content</constraint-name>
                        <text>'.self::esc($search).'</text>
                    </word-constraint-query>
                    <word-constraint-query>
                        <constraint-name>title</constraint-name>
                        <weight>10</weight>
                        <text>'.self::esc($search).'</text>
                    </word-constraint-query>
                </or-query>
            </query>
        ';

        // Api::logger()->debug($structure);

		//Possibility to modify the query after it was built
		// \apply_filters('mlphp_structure', $structure);

		//Possibility to modify the query after it was built
		\apply_filters('mlphp_query', $query);


        $results = null;

        try {
            $results = $query->retrieve($search, array(
                'options' => 'wms' 
            ));
        } catch (\Exception $ex) {
            Api::logger()->error($ex);
            return null;
        }

        
        /*
		try{
			$index = Api::index(false);

			$search = new \Elastica_Search($index->getClient());
			$search->addIndex($index);

			if($bytype){
				$search->addType($index->getType($bytype));
			}

			\apply_filters( 'elastica_pre_search', $search );

			$response = $search->search($query);
		}catch(\Exception $ex){
			return null;
		}
        */

        $ids = array();
        $scores = array();
        $snippets = array();

        foreach ($results->getResults() as $result) {
            $id = substr($result->getURI(), 1);
            $ids[] = $id;
            $scores[$id] = $result->getScore();
            $snippets[$id] = array();
            foreach ($result->getMatches() as $match) {
                $snippets[$id][] = $match;
            }
        }

        Api::logger()->debug("Total : " . $results->getTotal());

		$val = array(
			'total' => $results->getTotal(),
			'snippets' => $snippets,
			'scores' => $scores,
			'facets' => array(), 
            'ids' => $ids
		);

        /*
		foreach($response->getFacets() as $name => $facet){
			foreach($facet['terms'] as $term){
				$val['facets'][$name][$term['term']] = $term['count'];
			}
			if(isset($facet['ranges']) && $facet['ranges']){
				foreach($facet['ranges'] as $range){
					$val['facets'][$name][$range['from'] . '-' . $range['to']] = $range['count'];
				}
			}
		}

		foreach($response->getResults() as $result){
			$val['scores'][$result->getId()] = $result->getScore();
		}
        */

		// $val['ids'] = array_keys($val['scores']);

		//Possibility to alter the results
		return \apply_filters('marklogic_search_results', $val, $results);
	}

	public function highlight($search, $contentType, $content) {
        $query = new MLPHP\Search(Api::client());
        return $query->highlight($content, $contentType, "hit", $search);
    }

	protected function facet($name, $facets, $type, &$musts, &$filters, $translate = array()){
		if(isset($facets[$name]) && is_array($facets[$name])){
			foreach($facets[$name] as $operation => $facet){
				if(is_string($operation)){
					if($operation == 'and'){
						if(is_array($facet)){
							foreach($facet as $value){
								$musts[] = array( $type => array( $name => $translate[$value] ?: $value ));
							}
						}else{
							$musts[] = array( $type => array( $name => $translate[$facet] ?: $facet ));
						}
					}

					if($operation == 'or'){
						if(is_array($facet)){
							foreach($facet as $value){
								$filters[] = array( $type => array( $name => $translate[$value] ?: $value ));
							}
						}else{
							$filters[] = array( $type => array( $name => $translate[$facet] ?: $facet ));
						}
					}
				}else{
					$musts[] = array( $type => array( $name => $translate[$facet] ?: $facet ));
				}
			}
		}elseif(isset($facets[$name]) && $facets[$name]){
			$musts[] = array( $type => array( $name => $translate[$facets[$name]] ?: $facets[$name] ));
		}
	}
}

