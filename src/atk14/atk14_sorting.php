<?php
/**
 * Class for sorting records
 *
 * @package Atk14\Core
 * @filesource
 */

/**
 * Class that simplifies sorting of records.
 * It's closely connected from any template by {sortable} smarty helper.
 *
 *
 * Here is example of use.
 *
 * Within a controller's method:
 *
 * 	$sorting = new Atk14Sorting($this->params);
 * 	$sorting->add("name");
 * 	$sorting->add("created",array("reverse" => true));
 * 	$sorting->add("rank",array("ascending_ordering" => "rank DESC, id ASC", "descending_ordering" => "rank ASC, id DESC"));
 * 	$finder = TableRecord::Finder(array(
 * 		"class_name" => "Book",
 * 		"order" => $sorting->getOrder(),
 * 	));
 * 	$this->tpl_data["finder"] = $finder;
 * 	$this->sorting = $sorting;
 *
 * Within a template:
 * 	<table>
 * 		<thead>
 * 			<tr>
 * 				{sortable key=name}<th>Name</th>{/sortable}
 * 				{sortable key=created}<th>Create date</th>{/sortable}
 * 				...
 * 			</tr>
 * 		</thead>
 * 	</table>
 *
 * @package Atk14\Core
 * 
 */
class Atk14Sorting{

	/**
	 * @ignore
	 */
	private $_Ordering = array();

	/**
	 * @ignore
	 */
	private $_OrderingStrings = array();

	/**
	 * Constructor
	 *
	 * @param Dictionary $params Parameters from request
	 * @param array $options
	 */
	function __construct($params,$options = array()){
		$this->_Params = $params;
	}

	/**
	 * Adds a sorting key which represents a table column by default. You can assign own definition to a key.
	 *
	 * First added key is the default sorting key.
	 *
	 * Basic usage
	 * 	$sorting->add("create_date");
	 * 	$sorting->add("create_date",array("reverse" => true));
	 * 	$sorting->add("title",array("order_by" => "UPPER(title)"));
	 * 	$sorting->add("title",array(
	 * 		"asc" => "UPPER(title), id",
	 * 		"desc" => "UPPER(title) DESC, id DESC"
	 * 	));
	 * 	$sorting->add("title",array(
	 * 		"ascending_ordering" => "UPPER(title), id",
	 * 		"descending_ordering" => "UPPER(title) DESC, id DESC"
	 * 	));
	 * 	$sorting->add("title","UPPER(title), id", "UPPER(title) DESC, id DESC");
	 *
	 * @param string $key Name of the key which can then be used in a template by {sortable} helper.
	 * @param string|array $options_or_asc_ordering string for sql definition of ascending ordering or array for options. see description of $options below.
	 * @param string $desc_ordering sql definition of descending ordering
	 * @param array $options Options to customize sorting
	 * <ul>
	 * 	<li>order_by - </li>
	 * 	<li>ascending_ordering - specifies custom ascending ordering, eg. 'created,id asc'</li>
	 * 	<li>descending_ordering - specifies custom descending ordering, eg. 'created,id desc'</li>
	 * 	<li>reverse - used only in conjunction with 'order_by'. Reverts order for both descending and ascending ordering</li>
	 * 	<li>title - string for the title attribute of the generated <a /> tag.</li>
	 * </ul>
	 *
	 */
	function add($key,$options_or_asc_ordering = array(), $desc_ordering = null, $options = array()){
		$asc_ordering = null;

		if(is_array($desc_ordering)){
			$options = $desc_ordering;
			$desc_ordering = null;
		}

		if(is_array($options_or_asc_ordering)){
			$options = $options_or_asc_ordering;
		}

		if(is_string($options_or_asc_ordering)){
			$asc_ordering = $options_or_asc_ordering;
			if(!isset($desc_ordering)){
				$desc_ordering = preg_replace('/\sASC$/i','',$asc_ordering);
				$desc_ordering .= " DESC"; // TOTO: "name ASC, author ASC" -> "name DESC, author DESC"
			}
		}

		// shortcuts:
		//	 asc -> asc_ordering
		//	 desc -> desc_ordering
		foreach(array("asc","desc") as $_k){
			if(isset($options[$_k])){
				$options["{$_k}ending_ordering"] = $options[$_k];
				unset($options[$_k]);
			}
		}

		$options = array_merge(array(
			"order_by" => "$key",
			"ascending_ordering" => $asc_ordering,
			"descending_ordering" => $desc_ordering,
			"title" => _("Sort table by this column"),
			"reverse" => false,
		),$options);

		if(!isset($options["ascending_ordering"])){
			$options["ascending_ordering"] = "$options[order_by] ".($options["reverse"] ? "DESC" : "ASC");
		}
		if(!isset($options["descending_ordering"])){
			$options["descending_ordering"] = "$options[order_by] ".($options["reverse"] ? "ASC" : "DESC");
		}

		$this->_Ordering[$key] = $options;
		$this->_OrderingStrings["$key-asc"] = $options["ascending_ordering"];
		$this->_OrderingStrings["$key-desc"] = $options["descending_ordering"];
	}

	/**
	 * Returns the ordering key.
	 * It is a string and this form is suitable for usage in any finding method used by {@link DbMole}.
	 *
	 * @return string the ordering key
	 */
	function getOrder(){
		(($key = $this->_Params->g(ATK14_SORTING_PARAM_NAME,"string")) && isset($this->_OrderingStrings[$key])) || ($key = $this->_getDefaultKey());

		$this->_ActiveKey = $key;
		
		return $this->_OrderingStrings[$key];
	}

	/**
	 * @ignore
	 */
	private function _getDefaultKey(){
		$_ar = array_keys($this->_Ordering);
		return "$_ar[0]-asc";
	}

	/**
	 * Returns name of current sorting key
	 *
	 * @return string
	 */
	function getActiveKey(){
		if(!isset($this->_ActiveKey)){
			$this->getOrder();
		}
		return $this->_ActiveKey;
	}

	/**
	 * Returns string which is used to describe the sorting link.
	 *
	 * @param string $key Name of the key
	 * @return string Text shown on the sorting link
	 */
	function getTitle($key){
		return $this->_Ordering[$key]["title"];
	}

	/**
	 * Returns the string representation of the objects' instance.
	 *
	 */
	function toString(){ return $this->getOrder(); }

	/**
	 * Magical method to get string representation of the objects' instance.
	 *
	 *
	 */
	function __toString(){ return $this->toString(); }
}
