<?php
/**
 * This library performs various language and encoding related conversions. It also enables loading language files and changing the current language.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

// Load config file
	zajLib::me()->load->library('config');


class zajlib_lang extends zajlib_config {
	
	/**
	 * Contains the current locale. Available locales are set in the config file site/index.php.
	 **/
	 	private $current_locale;

	/**
	 * Contains the default locale. The default is set in the config file site/index.php.
	 **/
	 	private $default_locale;

	/**
	 * Contains all the available locales. These are set in the config file site/index.php.
	 **/
	 	private $available_locales;

	/**
	 * Extend the config file loading mechanism.
	 **/
		protected $dest_path = 'cache/lang/';	// string - subfolder where compiled conf files are stored (cannot be changed)
		protected $conf_path = 'lang/';			// string - default subfolder where uncompiled conf files are stored
		protected $type_of_file = 'language';	// string - the name of the file type this is (either configuration or language)

	/**
	 * Creates a new language library.
	 **/
	public function __construct(&$zajlib, $system_library) {
		parent::__construct($zajlib, $system_library);
		// set default locale and available locales
			$this->reload_locale_settings();
		// set my default locale
			$this->set();
	}

	/**
	 * Methods for loading and changing current language.
	 **/

		/**
		 * Get the current locale.
		 * @return string The locale code of the current language.
		 **/
		public function get(){
			// Return the current locale language
				return $this->current_locale;
		}

		/**
		 * Get the current two-letter language code based on the current locale.
		 * @return string The language code based on current locale.
		 **/
		public function get_code(){
			// Return the current locale language
				return substr($this->current_locale, 0, 2);
		}

		/**
		 * Change locale language to a new one.
		 * @param bool|string $new_language If set, it will try to choose this locale. Otherwise the default locale will be chosen.
		 * @return string Returns the name of the locale that was set.
		 */
	 	public function set($new_language = false){
	 		// Check to see if the language to be set is not false and is in locales available. If problem, set to default locale.
	 			if(!empty($new_language) && in_array($new_language, $this->available_locales)){
	 				$this->current_locale = $new_language;
	 			}
	 			else $this->current_locale = $this->default_locale;
			// Now if Wordpress is enabled, switch to locale
				if($this->zajlib->plugin->is_enabled('wordpress') && !empty($GLOBALS['sitepress']) && !is_admin()){
					$GLOBALS['sitepress']->switch_lang(substr($this->current_locale, 0, 2), true);
				}
			// Set locale
				setlocale(LC_TIME, $this->current_locale);
	 		// Return new locale
	 			return $this->current_locale;
		}

		/**
		 * Set the current language (locale) using a two-letter language code. In case two or more locales use the same two letter code, the first will be chosen. If possible, use {@link $this->set()} instead.
		 * @param string|bool $new_language If set, it will try to choose this language. Otherwise the default langauge will be chosen based on the default locale.
		 * @return string The two-letter language code based on current locale.
		 **/
		public function set_by_code($new_language = false){
			if(!empty($new_language)){
			// Let's see if we have a compatible locale
	 			$available_locales = explode(',', $this->zajlib->zajconf['locale_available']);	 			
	 			foreach($available_locales as $l){
	 				// If found, set the locale and return me
	 				$lcompare = substr($l, 0, 2);
	 				if($lcompare == $new_language){
	 					$this->set($l);
	 					return $lcompare;
	 				}
	 			}
	 		}
	 		// Not found, set to default locale and return it
	 			return substr($this->set(), 0, 2);
		}

		/**
		 * Try to set the locale automatically first by subdomain, then by top level domain, then by query string. Saves a cookie.
		 */
		public function auto(){
			// Set by subdomain, top level domain, query string, or by cookie
				// If the subdomain is two letters, it will consider it a language code
					if(strlen($this->zajlib->subdomain) == 2) $this->set_by_code($this->zajlib->subdomain);
				// If the tld is two letters, it will consider it a language code
					elseif(strlen($this->zajlib->tld) == 2) $this->set_by_code($this->zajlib->tld);
				// If there is a query string, set it to that either by code or by
					elseif(!empty($_GET['lang'])){
						// Is it a code or a locale?
						if(strlen($_GET['lang']) == 2) $this->set_by_code($_GET['lang']);
						else $this->set($_GET['lang']);
					}
				// Otherwise try to set by cookie. If no cookie, it will set default
					else $this->set($_COOKIE['lang']);

			// If the current locale is not the same as the cookie, then set a cookie
				// Get current
					$current = $this->get();
				// Set a cookie if not the same as current
					if($current != $_COOKIE['lang']) $this->zajlib->cookie->add('lang', $current);
			return $current;
		}

		/**
		 * Get default locales.
		 * @return string Returns the hard-coded default locale.
		 **/
		public function get_default_locale(){
			return $this->default_locale;
		}

		/**
		 * Returns true if the current locale is the default locale.
		 * @return boolean True if the current locale, false otherwise.
		 **/
		public function is_default_locale(){
			return ($this->default_locale == $this->get());
		}

	/**
	 * All locales.
	 **/
		/**
		 * Get all locales.
		 * @return array Returns an array of all available locales.
		 **/
		public function get_locales(){
			return $this->available_locales;
		}

		/**
		 * Get all the available two-letter language codes.
		 * @return array Returns an array of all available codes.
		 **/
		public function get_codes(){
			// Run through
				$codes = array();
				foreach($this->available_locales as $al){
					$code = substr($al, 0, 2);
					$codes[$code] = $code;
				}
			return $codes;
		}

		/**
		 * Reload all the available locales and default locale settings.
		 */
		public function reload_locale_settings(){
	 		$this->default_locale = $this->zajlib->zajconf['locale_default'];
	 		$this->available_locales = explode(',', $this->zajlib->zajconf['locale_available']);
		}


	/**
	 * Template loading based on current locale.
	 **/
		/**
		 * Display a specific template by searching for a locale file first.
		 * If the request contains zaj_pushstate_block, it will reroute to block. See Mozajik pushState support for more info.
		 * @param string $source_path The path to the template to be compiled relative to the active view folders. 
		 * @param boolean $force_recompile If set to true, the template file will be recompiled even if a cached version already exists. (False by default.)
		 * @param boolean $return_contents If set to true, the compiled contents will be returned by the function and not sent to the browser (as is the default).
		 * @param boolean $custom_compile_destination If set, this allows you to compile the template to a different location than the default. This is not recommended unless you really know what you are doing!
		 * @return string If requested by the $return_contents parameter, it returns the entire generated contents.
		 * @todo Add support so that template and block in lang will search all plugin folders as well.
		 **/
		function template($source_path, $force_recompile = false, $return_contents = false, $custom_compile_destination = false){
			// Cut off the .html (.htm not supported!)
				$base_source_path = substr($source_path, 0, -5);
			// Seach for a local source_path
				// Search first for current locale
					if(file_exists($this->zajlib->basepath.'app/view/'.$base_source_path.'.'.$this->current_locale.'.html')) return $this->zajlib->template->show($base_source_path.'.'.$this->current_locale.'.html', $force_recompile, $return_contents, $custom_compile_destination);
				// Next for default locale
					if(file_exists($this->zajlib->basepath.'app/view/'.$base_source_path.'.'.$this->default_locale.'.html')) return $this->zajlib->template->show($base_source_path.'.'.$this->default_locale.'.html', $force_recompile, $return_contents, $custom_compile_destination);
				// All failed, so finally, just include me
					return $this->zajlib->template->show($source_path, $force_recompile, $return_contents, $custom_compile_destination); 
		}
		
		/**
		 * Extracts a specific block from a template and displays only that. This is useful for ajax requests.
		 * @param string $source_path The path to the template to be compiled relative to the active view folders. 
		 * @param string $block_name The name of the block within the template.
		 * @param boolean $force_recompile If set to true, the template file will be recompiled even if a cached version already exists. (False by default.)
		 * @param boolean $return_contents If set to true, the compiled contents will be returned by the function and not sent to the browser (as is the default).
		 * @return bool|string
		 * @todo Add support so that template and block in lang will search all plugin folders as well.
		 **/
		function block($source_path, $block_name, $force_recompile = false, $return_contents = false){
			// Cut off the .html (.htm not supported!)
				$base_source_path = substr($source_path, 0, -4);
			// Seach for a local source_path
				// Search first for current locale
					if(file_exists($this->zajlib->basepath.'app/view/'.$base_source_path.'.'.$this->current_locale.'.html')) return $this->zajlib->template->block($base_source_path.'.'.$this->current_locale.'.html', $block_name, $force_recompile, $return_contents);
				// Next for default locale
					if(file_exists($this->zajlib->basepath.'app/view/'.$base_source_path.'.'.$this->default_locale.'.html')) return $this->zajlib->template->block($base_source_path.'.'.$this->default_locale.'.html', $block_name, $force_recompile, $return_contents);
				// All failed, so finally, just include me
					return $this->zajlib->template->block($source_path, $block_name, $force_recompile, $return_contents); 
		}

		/**
		 * Override my load method for loading language files
		 **/

		/**
		 * Loads a langauge file at runtime. The file name can be specified two ways: either the specific ini file or just the name with the locale and extension automatic.
		 * For example: if you specify 'admin_shop' as the first parameter with en_US as the locale, the file lang/admin/shop.en_US.lang.ini will be loaded. If it is not found, the default locale will also be searched.
		 * @param string $name_OR_source_path The name of the file (without locale or ini extension) or the specific ini file to load.
		 * @param bool|string $section The section to compile.
		 * @param boolean $force_compile This will force recompile even if a cached version already exists.
		 * @param boolean $fail_on_error If set to true (the default), it will fail with error.
		 * @param boolean $load_default_locale_on_error If set to true (the default), it will load up the default locale if it failed to load the current lang.
		 * @return bool
		 */
		public function load($name_OR_source_path, $section=false, $force_compile=false, $fail_on_error=true, $load_default_locale_on_error=true){
			// First let's see if . is not found in path. If so, this is a name, so figure out what source path is based on current locale
				if(strstr($name_OR_source_path, '.') === false){
					// Assemble my file
						$original_source_path = $name_OR_source_path;
						$name_OR_source_path = $name_OR_source_path.'.'.$this->get().'.lang.ini';
					// First, try to load the default
						$result = parent::load($name_OR_source_path, $section, $force_compile, false);
					// Now if load failed, set load to the default locale
						if(!$result && $load_default_locale_on_error){
							// throw a warning (if not testing)
							if(!$this->zajlib->test->is_running()) $this->zajlib->warning("The language file $name_OR_source_path was not found, reverting to default locale.");
							$name_OR_source_path = $original_source_path.'.'.$this->get_default_locale().'.lang.ini';
						}
						else return true;
				}
			// Now just load the file as if it were a usual config and return
				return parent::load($name_OR_source_path, $section, $force_compile, $fail_on_error);
		}

		/**
		 * Sets the key/value variable object. Be careful, this overwrites the entire current setting. Because conf and lang are actually the same both values will also be overwritten.
		 * @param stdClass $variables The key/value pairs to use for the new variable.
		 * @return bool Always returns true.
		 */
		public function set_variables($variables){
			return $this->zajlib->config->set_variables($variables);
		}

		/**
		 * Sets the key/value variable object. Be careful, this overwrites the entire current setting. Because conf and lang are actually the same both are reset.
		 * @return bool Always returns true.
		 */
		public function reset_variables(){
			return $this->zajlib->config->reset_variables();
		}

	/**
	 * Other language-specific methods.
	 **/

		/**
		 * Converts a string to their standard latin-1 alphabet counterparts.
		 * @param string $str The original accented UTF string.
		 * @param bool $strip_newlines If set to true, new lines will be removed.
		 * @return string Returns a string without accents.
		 */
		function convert_eng($str, $strip_newlines = true){
			// now try to translate all characters to iso1
				$str = $this->convert($str, "ISO-8859-1", "UTF-8");
			// now remove newlines
				if($strip_newlines){
					$str = str_ireplace("\n", " ", $str);
					$str = str_ireplace("\r", "", $str);
				}
			return $str;
		}
		
		/**
		 * Strip non-alpha-numerical characters from a string.
		 * @param string $str The original accented string.
		 * @param boolean $convert_accents If set to true, accented characters will be converted before everything is stripped.
		 * @return string Returns a string without accents and only alpha-numerical characters
		 **/
		function strip_chars($str, $convert_accents = true){
			// convert accents?
				if($convert_accents) $str = $this->convert_eng($str);
			// now strip all non-alphanum
				$str = preg_replace("/[^[:alnum:]]/", "", $str);
			return $str;
		}
		
		/**
		 * Converts a string from one encoding to another.
		 * @param string $str The original string.
		 * @param string $to The destination encoding. ISO latin 1 by default.
		 * @param string $from The original encoding. UTF-8 by default.	 
		 * @param boolean $use_iconv Will force the function to use iconv. This will work on some systems, not on others.
		 * @return string Returns a string converted to the new encoding.
		 **/
		function convert($str, $to="ISO-8859-1", $from="UTF-8", $use_iconv = false){
			// try to convert using iconv
				if($use_iconv) return iconv($from, $to."//TRANSLIT//IGNORE", $str);
			// try to convert using html entities and manual conversions. you may add additional characters here for more languages!
				else{
					$text = mb_convert_encoding($str,'HTML-ENTITIES',$from);
					$text = preg_replace(
						array('/&szlig;/','/&(..)lig;/', '/&([aouAOU])uml;/','/&#337;/', '/&#336;/', '/&#369;/', '/&#368;/', '/&(.)[^;]*;/'),
						array('ss',"$1","$1".'e', 'o', 'O', 'u', 'U', "$1"),
					$text);
					return $text;
				}
		}
	
		/**
		 * UTF-safe substring. This is now depricated, use the built-in mb_substr instead.
		 * @param string $str The original accented string.
		 * @param integer $from The original accented string.
		 * @param integer $len The original accented string.
		 * @return string
		 * @ignore
		 * @todo Depricated! Remove this from 1.0. Use mb_substr.
		 **/
		function utf8_substr($str,$from,$len){
			return mb_substr($str, $from, $len);
		}
		
		/**
		 * Currency display. Depricated.
		 * @ignore
		 * @todo Remove this from 1.0
		 **/
		function currency($num){
			global $lang;
			return number_format($num, $lang[currency_valto],$lang[currency_tized],$lang[currency_ezer]);
		}
		
		
		/**
		 * Convert from central european ISO to UTF
		 * @ignore
		 * @todo Remove this from 1.0
		 **/
		function ISO2UTF($str){
			return iconv("ISO-8859-2", "UTF-8", $str);
		}
	
		/**
		 * Convert from UTF to central european ISO
		 * @ignore
		 * @todo Remove this from 1.0
		 **/
		function UTF2ISO($str){
			return iconv("UTF-8", "ISO-8859-2", $str);
		}	
		/**
		 * Replaces language-related sections in a string, but this is depricated so don't use!
		 * @ignore
		 * @todo Remove this from version 1.0
		 **/
		function replace($search, $replace, $subject){
			return str_ireplace("%".$search."%", $replace, $subject);
		}

}