<?php
/**
 * A class for storing custom fields
 */
class CustomFieldEntry extends zajModel{

	/**
	 * __model function. creates the database fields available for objects of this class.
	 * @param bool|stdClass $f The field's object generated by the child class.
 	 * @return stdClass Returns an object containing the field settings as parameters.
	 */
	static function __model($f = false){
		/////////////////////////////////////////
		// begin custom fields definition:
        if($f === false) $f = new stdClass();

		$f->customfield = zajDb::manytoone('CustomField');
		$f->value = zajDb::text();
		$f->class = zajDb::text();
		$f->parent = zajDb::text();
		$f->field = zajDb::text();

		// do not modify the line below!
        return parent::__model($f);
	}

	/**
	 * Construction and required methods
	 */
	public function __construct($id = ""){ parent::__construct($id, __CLASS__); return true; }
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }

	///////////////////////////////////////////////////////////////
	// !Custom methods
	///////////////////////////////////////////////////////////////

	public function __afterFetch(){
	}
}