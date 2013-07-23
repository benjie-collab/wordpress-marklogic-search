<?php
namespace MarkLogic\WordPressSearch;

use MarkLogic\MLPHP;

class Searcher{

    static function esc($s) {
        return htmlentities($s, ENT_COMPAT, "UTF-8");
    }

	public function query($search, $pageIndex, $size, $facets = array()) {

        $options = new MLPHP\Options(Api::client());

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
        $facets = array();

        foreach ($results->getResults() as $result) {
            $id = Document::uri_to_id($result->getURI());
            $ids[] = $id;
            $scores[$id] = $result->getScore();
        }

        Api::logger()->debug("Total : " . $results->getTotal());

		$val = array(
			'total' => $results->getTotal(),
			'scores' => $scores,
			'facets' => $facets,
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

