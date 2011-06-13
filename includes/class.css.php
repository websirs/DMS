<?php
/**
 * 
 *
 *  Write Dynamic CSS to file
 *
 *
 *  @package PageLines Core
 *  @subpackage Sections
 *  @since 4.0
 *
 */
class PageLinesCSS {
	
	function create( $format = 'inline') {
		
		if( $format == 'texturize' ){
			
			$this->nl = "\n";
			$this->nl2 = "\n\n";
			$this->comments = true;
			
		} else {
			
			$this->nl = "";
			$this->nl2 = "";
			$this->comments = false;
			
		}
		
		$this->intro();
		$this->typography();
		$this->layout();
		$this->options();
		$this->custom_css();
		
	}

	function intro(){
		if( $this->comments ) $this->css .= "/* PageLines - Copyright 2011 - Version ".CORE_VERSION." */".$this->nl2;
	}
	
	function typography(){
		
		foreach (get_option_array() as $mid){
			
			foreach($mid as $oid => $o){ 
				
				if($o['type'] == 'typography'){
					
					$type_foundry = new PageLinesFoundry;

					$type = pagelines_option($oid);
					
					$font_id = $type['font'];
					
					// Don't render if font isn't set.
					if(isset($font_id) && isset($type_foundry->foundry[$font_id]) ){
						
						if($type_foundry->foundry[$font_id]['google'])
							$google_fonts[] = $font_id;

						$type_selectors = $o['selectors']; 

						if( isset($type['selectors']) && !empty($type['selectors']) ) $type_selectors .=  ',' . trim(trim($type['selectors']), ",");

						$type_css = $type_foundry->get_type_css($type);
					
					
						$type_css_keys[] = $type_selectors . "{".$type_css."}".$this->nl;
					}
					
				}
				
			}
		}
		
		if(isset($google_fonts) && is_array($google_fonts )){
			
			if( $this->comments ) $this->css .= '/* Import Google Fonts --------------- */'.$this->nl2;
			
			$this->css .= $type_foundry->google_import($google_fonts) . $this->nl;
			
		}
		
		if( $this->comments ) $this->css .= '/* Set Type --------------- */'.$this->nl2;
		
		// Render the font CSS
		if(isset($type_css_keys) && is_array($type_css_keys)){
			foreach($type_css_keys as $typeface){
				$this->css .= $typeface .$this->nl;
			}
		}

	}

	function layout(){
		
		global $pagelines_layout; 
		global $post; 

		$content_width = $pagelines_layout->content->width;
		
		if( $this->comments ) 
			$this->css .= '/* Dynamic Layout --------------- */'.$this->nl2;
		
		/* Fixed Width Page */
		$fixed_page = $content_width + 20;
		$this->css .= ".fixed_width #page, .fixed_width #footer, .canvas #page-canvas{ max-width:".$fixed_page."px }".$this->nl;

		
		/* Content Width */
		$content_with_border = $content_width + 2;
		$this->css .= "#page-main .content{ max-width:".$content_with_border."px }".$this->nl;
	
		if(pagelines_option('responsive_layout'))
			$this->css .= "#site .content, .wcontent, #footer .content{ width: 100%; max-width:".$content_width."px;}".$this->nl2;
		else
			$this->css .= "#site .content, .wcontent, #footer .content{ width:".$content_width."px;}".$this->nl2;
	
		//$this->css .= "#site{min-width: 100%}".$this->nl; // Fix small horizontal scroll issue
		
		// For inline CSS in Multisite
		// TODO clean up layout variable handling
		$page_layout = $pagelines_layout->layout_mode;
		
		/* Layout Modes */
		foreach(get_the_layouts() as $layout_mode){
			
			$pagelines_layout->build_layout($layout_mode);
			
			$mode = '.'.$layout_mode.' ';
		
		
			/* (target / context)*100 = percent-result */
			$colwrap_width = $this->get_width( $pagelines_layout->column_wrap->width, $content_width ); 
			$sbwrap_width = $this->get_width( $pagelines_layout->sidebar_wrap->width, $content_width );

			$main_width = $this->get_width( $pagelines_layout->main_content->width, $pagelines_layout->column_wrap->width );
			
			$sb2_width = $this->get_width( $pagelines_layout->sidebar2->width, $pagelines_layout->sidebar_wrap->width );
			
			if($pagelines_layout->layout_mode == 'two-sidebar-center')
				$sb1_width = $this->get_width( $pagelines_layout->sidebar1->width, $pagelines_layout->column_wrap->width ); 
			else
				$sb1_width = $this->get_width( $pagelines_layout->sidebar1->width, $pagelines_layout->sidebar_wrap->width );
	
			$this->css .= sprintf('%1$s #pagelines_content #column-main, %1$s .wmain, %1$s #buddypress-page #container{ %2$s }%3$s', $mode, $main_width, $this->nl);
			$this->css .= sprintf('%1$s #pagelines_content #sidebar1, %1$s #buddypress-page #sidebar1{ %2$s }%3$s', $mode, $sb1_width, $this->nl);
			$this->css .= sprintf('%1$s #pagelines_content #sidebar2, %1$s #buddypress-page #sidebar2{ %2$s }%3$s', $mode, $sb2_width, $this->nl);
			$this->css .= sprintf('%1$s #pagelines_content #column-wrap, %1$s #buddypress-page #container{ %2$s }%3$s', $mode, $colwrap_width, $this->nl);
			$this->css .= sprintf('%1$s #pagelines_content #sidebar-wrap, %1$s #buddypress-page #sidebar-wrap{ %2$s }%3$s', $mode, $sbwrap_width, $this->nl2);
			
		}
		
		// Put back to original mode for page layouts in multisite
		$pagelines_layout->build_layout($page_layout);
		
	}
	
	function get_width($target, $context){
		return sprintf( 'width:%s%%;', ($context != 0 ) ? ( $target / $context ) * 100 : 0 );
	}
	
	
	function options(){
		/*
			Handle Color Select Options and output the required CSS for them...
		*/
		if( $this->comments ) $this->css .= '/* Options --------------- */'.$this->nl2;
		foreach (get_option_array() as $menuitem){

			foreach($menuitem as $optionid => $option_info){ 
				
				if($option_info['type'] == 'css_option' && pagelines_option($optionid)){
					if(isset($option_info['css_prop']) && isset($option_info['selectors'])){
						
						$css_units = (isset($option_info['css_units'])) ? $option_info['css_units'] : '';
						
						$this->css .= $option_info['selectors'].'{'.$option_info['css_prop'].':'.pagelines_option($optionid).$css_units.';}'.$this->nl;
					}

				}
				
				if( $option_info['type'] == 'background_image' && pagelines_option($optionid.'_url')){
					
					$bg_repeat = (pagelines_option($optionid.'_repeat')) ? pagelines_option($optionid.'_repeat'): 'no-repeat';
					$bg_pos_vert = (pagelines_option($optionid.'_pos_vert') || pagelines_option($optionid.'_pos_vert') == 0 ) ? (int) pagelines_option($optionid.'_pos_vert') : '0';
					$bg_pos_hor = (pagelines_option($optionid.'_pos_hor') || pagelines_option($optionid.'_pos_hor') == 0 ) ? (int) pagelines_option($optionid.'_pos_hor') : '50';
					$bg_selector = (pagelines_option($optionid.'_selector')) ? pagelines_option($optionid.'_selector') : $option_info['selectors'];
					$bg_url = pagelines_option($optionid.'_url');
					
					$this->css .= $bg_selector ."{background-image:url('".$bg_url."');}".$this->nl;
					$this->css .= $bg_selector ."{background-repeat:".$bg_repeat.";}".$this->nl;
					$this->css .= $bg_selector ."{background-position:".$bg_pos_hor."% ".$bg_pos_vert."%;}".$this->nl;
					
					
				}
	
				
				if($option_info['type'] == 'colorpicker'){
					
					$this->_css_colors($optionid, $option_info['selectors'], $option_info['css_prop']);

				}
				
				elseif($option_info['type'] == 'color_multi'){
					
					foreach($option_info['selectvalues'] as $moption_id => $m_option_info){
						
						$the_css_selectors = (isset($m_option_info['selectors'])) ? $m_option_info['selectors'] : null ;
						$the_css_property = (isset($m_option_info['css_prop'])) ? $m_option_info['css_prop'] : null ;
						
						$this->_css_colors($moption_id, $the_css_selectors, $the_css_property);
					}
					
				}
			} 
		}
		$this->css .= $this->nl2;
	}
	
	function _css_colors( $optionid, $selectors = null, $css_prop = null ){
		if( pagelines_option($optionid) ){
			
			if(isset($css_prop)){
			
				if(is_array($css_prop)){
				
					foreach( $css_prop as $css_property => $css_selectors ){

						if($css_property == 'text-shadow'){
							$this->css .= $css_selectors . '{ text-shadow:'.pagelines_option($optionid).' 0 1px 0;}'.$this->nl;		
						} elseif($css_property == 'text-shadow-top'){
							$this->css .= $css_selectors . '{ text-shadow:'.pagelines_option($optionid).' 0 -1px 0;}'.$this->nl;		
						}else {
							$this->css .= $css_selectors . '{'.$css_property.':'.pagelines_option($optionid).';}'.$this->nl;		
						}
						
					}
				
				}else{
					$this->css .= $selectors.'{'.$css_prop.':'.pagelines_option($optionid).';}'.$this->nl;
				}
			
			} else {
				$this->css .= $selectors.'{color:'.pagelines_option($optionid).';}'.$this->nl;
			}
		}
	}
	
	function custom_css(){
		if( $this->comments )  $this->css .= '/* Custom CSS */'.$this->nl2;
		$this->css .= pagelines_option('customcss');
		$this->css .= $this->nl2;
	}

}

/**
 * 
 *
 *  Write Dynamic CSS to file
 *
 *  @package PageLines Core
 *  @subpackage Sections
 *  @since 1.2.0
 *
 */
function pagelines_build_dynamic_css( $trigger = 'N/A' ){

	global $blog_id;

	// Create directories and folders for storing dynamic files
	if(!file_exists(PAGELINES_DCSS) ) {
		if ( false === pagelines_make_uploads() ); {
			pagelines_update_option( 'inline_dynamic_css', true );
			return;
		}	
	}
	// Write to dynamic files
	if ( is_writable(PAGELINES_DCSS) && (!is_multisite() || (is_multisite() && $blog_id == 1) ) ){
		$pagelines_dynamic_css = new PageLinesCSS;
		$pagelines_dynamic_css->create('texturize');
		pagelines_make_uploads($pagelines_dynamic_css->css ."\n\n/* Trigger: ". $trigger . '*/');
	}

}

/**
 * 
 *  Load Dynamic CSS inline
 *
 *  @package Platform
 *  @since 1.2.0
 *
 */
function get_dynamic_css(){
	$pagelines_dynamic_css = new PageLinesCSS;
	$pagelines_dynamic_css->create();
	echo '<style type="text/css" id="dynamic-css">'."\n". $pagelines_dynamic_css->css . "\n".'</style>'. "\n";
}
/********** END OF CSS CLASS  **********/