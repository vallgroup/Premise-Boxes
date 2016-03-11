<?php
/**
 * PremiseField Class
 *
 * Create form fields by instantiating this class and passing some parameters. 
 * Each field will be an instance of this class.
 *
 * @see library/premise-field-library.php For functions that utilize this class
 *
 * @package Premise WP
 * @subpackage Model
 */
class PremiseField {


	/**
	 * Holds type attribute for field
	 * 
	 * @var string
	 */
	protected $type = 'text';


	

	/**
	 * Defaults for each field
	 *
	 * Special Parameters: This parameters are special because they alter the
	 * HTML markup of a field or add functionality such as filters.
	 *
	 * Normal Parameters: This parameters (and every other paramaters passed) act as
	 * additional attributes added the the field. We add some defaults
	 * and some additional params to make your life easier like 'default'
	 * or 'options'
	 * 
	 * @var array
	 *
	 * @since 1.2 Moved type oustide of arguments and other changes
	 */
	protected $defaults = array(
		/**
		 * Special Parameters
		 */
		'label'      => '',      // Wraps label element around field. uses id for for attribute if id not empty
		'tooltip'    => '',      // Adds a tooltip and tooltip functionality to field
		'add_filter' => '',      // Add a filter to this field. Read documentation for list of filters
		'context'    => '',      // Used to let Premise know where to retrieve values from ( post, user )
		/**
		 * Normal Parameters
		 */
		'name'       => '',      // name attribute. if empty fills from id
		'id'         => '',      // id attribute. is empty fills from name
		'value'      => '',      // value attribute. by default tries to get_option(name)
		'value_att'  => '',      // value attribute. Used for checkboxes and radio
		'default'    => '',      // if value is empty and get_option() return false
		'options'    => array(), // options for select fields in this format ( Text => Value )
		'attribute'  => '',      // html attributes to add to element i.e. onchange="doSomethingCool()"
	);




	/**
	 * holds initial agrumnets passed to the class
	 * 
	 * @var array
	 */
	protected $args = array();




	/**
	 * Parsed arguments for field
	 * 
	 * @var array
	 */
	protected $field = array();




	/**
	 * Holds the html for this field
	 * 
	 * @var string
	 */
	public $html = '';




	/**
	 * Holds the field label including tooltip
	 * 
	 * @var string
	 */
	public $label = '';




	/**
	 * Holds the field raw html
	 * 
	 * @var string
	 */
	public $field_html = '';




	/**
	 * will hold our button markup to upload wp media
	 * 
	 * @var string
	 */
	protected $btn_upload_file = '<a 
		class="premise-btn-upload" 
		href="javascript:void(0);" 
		onclick="PremiseField.WPMedia.init(this)"
		><i class="fa fa-fw fa-upload"></i></a>';




	/**
	 * Holds the button for removing wp media uploaded
	 * 
	 * @var string
	 */
	protected $btn_remove_file = '<a 
		class="premise-btn-remove" 
		href="javascript:void(0);" 
		onclick="premiseRemoveFile(this)"
		><i class="fa fa-fw fa-times"></i></a>';




	/**
	 * Holds our fa_icon insert btn
	 * 
	 * @var string
	 */
	protected $btn_insert_icon = '<a 
		href="javascript:void(0);" 
		class="premise-choose-icon" 
		><i class="fa fa-fw fa-th"></i></a>';





	/**
	 * holds our fa_icon remove btn
	 * 
	 * @var string
	 */
	protected $btn_remove_icon = '<a 
		href="javascript:void(0);" 
		class="premise-remove-icon" 
		><i class="fa fa-fw fa-times"></i></a>';




	/**
	 * holds our fontawesome icons. assigned on prepare_field();
	 * 
	 * @var array
	 */
	public $fa_icons = array();




	/**
	 * Stores all filters that were used for a particular field
	 *
	 * @since 1.2 added to remove filters at the end
	 *
	 * @see remove_filters() runs at the end to make sure no filters repeat
	 * 
	 * @var array
	 */
	protected $filters_used = array();




	/**
	 * construct our object
	 * 
	 * @param array $args array holding one or more fields
	 */
	function __construct( $type = '', $args ) {

		if ( ! empty( $type ) && is_string( $type ) )
			$this->type = $type;

		if( ! empty( $args ) && is_array( $args ) )
			$this->args = $args;

		if ( ( empty( $type ) && is_array( $args ) ) && array_key_exists( 'type', $args ) ) {
			$this->type = $args['type'];
			unset( $args['type'] );
			$this->args = $args;
		}

		/**
		 * Initiate the field
		 */
		$this->field_init();

	}




	/**
	 * begin processing the field
	 */
	protected function field_init() {

		/**
		 * parse defaults and arguments
		 */
		$this->set_defaults();

		/**
		 * get everything ready to build the field
		 */
		$this->prepare_field();

		/**
		 * build the field
		 */
		$this->build_field();
				
	}






	/**
	 * Merge defaults with arguments passed to our object
	 *
	 * Saves all arguments into one array of arrays held by $field property.
	 *
	 * @return array all arguments. array of arrays.
	 */
	protected function set_defaults() {

		/**
		 * parse defaults and arguments
		 * 
		 * @var array
		 */
		$field = wp_parse_args( $this->args, $this->defaults );

		/**
		 * Make sure our field has its necessary values
		 *
		 * Get the name field first since it is needed for the value field to be retreived.
		 */
		$field['name']  = $this->get_name();
		$field['value'] = $this->get_db_value();
		$field['id']    = $this->get_id_att();

		/**
		 * assign common attributes
		 *
		 * This allows the user to submit a param within the array of arguments in a simpler manner, like array( 'required' => true )
		 * instead of array( 'required' => 'required' ).
		 *
		 * This params are also used in some places in our object to insert or add additioinal functionality to a field. 
		 * e.g. On the select field if 'multiple' has been passed then we handle our options differenlty (array instead of string).
		 *
		 * @since 1.2 
		 */
		$field['required'] = ( isset( $field['required'] ) && $field['required'] ) ? 'required' : '';
		$field['multiple'] = ( isset( $field['multiple'] ) && $field['multiple'] ) ? 'multiple' : '';
		$field['disabled'] = ( isset( $field['disabled'] ) && $field['disabled'] ) ? 'disabled' : '';

		$this->field = $field;
	}






	/**
	 * Prepare our field. This function assigns the values to the 
	 * class properties needed to build a particular field
	 */
	protected function prepare_field() {

		/**
		 * add filters before we do anything else
		 */
		$this->add_filters();

		/**
		 * prep the label element
		 */
		$this->the_label();

		/**
		 * prep the field element
		 */
		$this->the_field();
	}





	/**
	 * Add filters to a field
	 *
	 * This has to run first to make sure that our filters get hooked before they are called
	 * Unsets the filter argument at the end to avoid conflicts when printing attributes on field
	 *
	 * @since 1.2 
	 */
	protected function add_filters() {
		
		if ( ! empty( $this->field['add_filter'] ) && strpos( $this->field['add_filter'], ':' ) ) {

			$filter = explode( ':', $this->field['add_filter'] );

			add_filter( $filter[0], $filter[1] );

			array_push( $this->filters_used, $filter[0] );
		}

		if ( 'fa_icon' == $this->type ) {
			add_filter( 'premise_field_html_after_wrapper', array( $this, 'fa_icons' ) );
			array_push( $this->filters_used, 'premise_field_html_after_wrapper' );
		}

		if ( 'checkbox' == $this->type || 'radio' == $this->type ) {
			// Make sure value_att property has a value otherwise we have nothing to check the value against
			$this->field['value_att'] = isset( $this->field['value_att'] ) && ! empty( $this->field['value_att'] ) ? $this->field['value_att'] : '1';
			add_filter( 'premise_field_label_html', array( $this, 'silent_label' ) );
			array_push( $this->filters_used, 'premise_field_label_html' );
		}
		
		unset( $this->field['add_filter'] );
	}





	/**
	 * Saves and returns the label html element
	 *
	 * @since 1.2 
	 * 
	 * @return string HTML for label element
	 */
	protected function the_label() {
		$label = '';

		if ( ! empty( $this->field['label'] ) ) {
			$label .= '<label';
			$label .= ! empty( $this->field['id'] )       ? ' for="'.esc_attr( $this->field['id'] ).'">'                                           : '>';
			$label .= esc_attr( $this->field['label'] );
			$label .= ! empty( $this->field['required'] ) ? ' <span class="premise-required">*</span>'                                             : '';
			$label .= ! empty( $this->field['tooltip'] )  ? ' <span class="premise-tooltip"><i>'.esc_attr( $this->field['tooltip'] ).'</i></span>' : '';
			$label .= '</label>';
		}

		/**
		 * Alter the label html
		 *
		 * this filter allows you to change the html of the label element of a field
		 * passes the generated html to the function. additionaly paramters are all
		 * the field arguments and the type of field being called.
		 *
		 * @since 1.2 Added with new premise field class
		 * 
		 * @premise-hook premise_field_label_html do hook for label html string
		 *
		 * @var string
		 */
		$this->label = apply_filters( 'premise_field_label_html', $label, $this->field, $this->type );
	}




	/**
	 * The field's html
	 * 
	 * @return string html for the raw field
	 */
	protected function the_field() {
		
		$html =''; // Start with a clean HTML string
		
		/**
		 * Build field depending on the type passed
		 */
		switch( $this->type ) {
			case 'select':
				$html .= $this->select_field();
				break;

			case 'textarea':
				$html .= $this->textarea();
				break;

			case 'checkbox':
				$html .= $this->checkbox();
				break;

			case 'radio':
				$html .= $this->radio();
				break;

			case 'wp_media':
				$html .= $this->wp_media();
				break;

			case 'fa_icon':
				$html .= $this->fa_icon();
				break;

			case 'video':
				$html .= $this->video();
				break;

			case 'wp_color':
				$html .= $this->wp_color();
				break;

			default:
				$html .= $this->input_field();
				break;
		}

		/**
		 * filter the field html
		 *
		 * Allow you to change the html passed to the field element
		 *
		 * @since 1.2 
		 *
		 * @premise-hook premise_field_raw_html filter the html for the field itself
		 * 
		 * @var string
		 */
		$this->field_html = apply_filters( 'premise_field_raw_html', $html, $this->field, $this->type );
	}






	/**
	 * Builds our field and saves the html markup for it
	 * in our object
	 *
	 * @return string HTML for field
	 */
	protected function build_field() {

		/**
		 * html for actual field
		 * 
		 * @var string
		 */
		$html = '<div class="'.$this->get_wrapper_class().'">';
		
			$html .= $this->label;

			$html .= '<div class="premise-field-'.$this->type.'">';

				$html .= $this->field_html;

			$html .= '</div>';

			/**
			 * Insert your own markup after the field
			 *
			 * @since 1.2 
			 *
			 * @premise-hook premise_field_html_after_wrapper insert html after the field wrapper
			 *
			 * @var string has to return html string
			 */
			$html .= apply_filters( 'premise_field_html_after_wrapper', '', $this->field, $this->type );

		$html .= '</div>';

		/**
		 * filter the entire html
		 *
		 * Allow you to change the html passed to the field element
		 *
		 * @since 1.2 
		 *
		 * @premise-hook premise_field_html filter the html for the whole field
		 * 
		 * @var string
		 */
		$this->html = apply_filters( 'premise_field_html', $html, $this->field, $this->type );

		/**
		 * Remove filters
		 *
		 * @since 1.2 remove filters used
		 */
		$this->remove_filters();
	}




	/**
	 * create an input field
	 * 
	 * @return string html for an input element
	 */
	protected function input_field() {

		$field  = '<input type="'. $this->type .'"';

		$field .= $this->get_atts();
		
		$field .= '>';

		/**
		 * Filter to alter html of input field after creating it
		 *
		 * @since 1.2 added to offer more control over markup
		 *
		 * @premise-hook premise_field_input filter the input field html
		 */
		return apply_filters( 'premise_field_input', $field, $this->field, $this->type );

	}







	/**
	 * Textarea element
	 * 
	 * @return string html for textarea
	 */
	protected function textarea() {
		
		$field = '<textarea';

		$field .= $this->get_atts();

		$field .= '>'.$this->field['value'].'</textarea>';

		/**
		 * premise_field_textarea
		 * 
		 * @premise-hook premise_field_textarea filter the textarea field html
		 *
		 * @since 1.2
		 */
		return apply_filters( 'premise_field_textarea', $field, $this->field, $this->type );
	}






	/**
	 * create a checkbox field
	 * 
	 * @return string html for checkbox field
	 */
	protected function checkbox() {
		
		$field  = '<input type="'. $this->type .'"';

		$field .= ! empty( $this->field['value_att'] ) ? ' value="' . $this->field['value_att'] . '"' : 'value="1"';
		
		$field .= $this->get_atts();

		$field .= '><label for="'.esc_attr( $this->field['id'] ).'" class="premise-field-state"></label>';

		return $field;

	}






	/**
	 * create a radio field
	 * 
	 * @return string html for radio element
	 */
	protected function radio() {
		
		$field  .= '<input type="'.$this->type.'"';

		$field .= ! empty( $this->field['value_att'] ) ? ' value="' . $this->field['value_att'] . '"' : 'value="1"';
		
		$field .= $this->get_atts();

		$field .= '><label for="'.esc_attr( $this->field['id'] ).'" class="premise-field-state"></label>';

		return $field;
	}





	/**
	 * Silent Label
	 *
	 * Return a label for field that is not clickable
	 * Used for checkbox fields and radio fields
	 *
	 * @since 1.2 
	 * 
	 * @return string returns silent label
	 */
	public function silent_label( $label ) {
		return str_replace( array( '<label', '</label>' ), array( '<p class="premise-label"', '</p>' ), $label );
	}





	/**
	 * create select field
	 * 
	 * @return string html for select field
	 */
	protected function select_field() {
		
		$field  = '<select';

		$field .= $this->get_atts();

		$field .= '>' . $this->select_options() . '</select>';

		return $field;
	}






	/**
	 * create select field options
	 * 
	 * @return string options elements for select dropdown
	 */
	protected function select_options() {
		
		$options = '';

		if( is_array( $this->field['value'] ) ) {

			foreach ( (array) $this->field['options'] as $key => $value ) {
				$options .= '<option  value="'.$value.'"';
				$options .= (is_array( $this->field['value'] ) && in_array( $value, $this->field['value'] ) ) ? 'selected="selected"' : '';
				$options .= '>'.$key.'</option>';
			}
		}
		else { 
			foreach ( (array) $this->field['options'] as $key => $value) {
				$options .= '<option  value="'.$value.'"';
				$options .= selected( $this->field['value'], $value, false );
				$options .= '>'.$key.'</option>';
			}	
		}

		return $options;
	}






	/**
	 * create wp media upload field
	 *
	 * this field allow you to upload files using wordpress's
	 * own media upload ui.
	 *
	 * @since 1.2 replace the file type since now you can use that independently
	 * 
	 * @return string html for wo media upload field
	 */
	protected function wp_media() {

		/**
		 * We our own filter to alter the html of our input field
		 */
		add_filter( 'premise_field_input', array( $this, 'wp_media_input' ) );

		/**
		 * call the input field. 
		 * 
		 * This will be alter due to our hook above
		 * 
		 * @var string
		 */
		$field = $this->input_field();

		/**
		 * Filter to alter the html on the media upload btn
		 *
		 * @since 1.2
		 *
		 * @premise-hook premise_field_upload_btn filter the wp media upload button
		 */
		$field .= apply_filters( 'premise_field_upload_btn', $this->btn_upload_file, $this->field );

		/**
		 * Filter to alter the html on the media remove button
		 *
		 * @since 1.2 
		 * 
		 * @premise-hook premise_field_remove_btn filter the wp media remove button
		 */
		$field .= apply_filters( 'premise_field_remove_btn', $this->btn_remove_file, $this->field );

		return apply_filters( 'premise_field_wp_media_html', $field, $this->field, $this->type );
	}




	/**
	 * build our wp media input field
	 *
	 * @since 1.2
	 * 
	 * @param  string $field the html for the field default
	 * @return string        the new html for the field
	 */
	public function wp_media_input( $field ) {
		return str_replace( 'type="wp_media"', 'type="text" class="premise-file-url"', $field );
	}





	/**
	 * build fa_icon field
	 *
	 * @since 1.2 
	 * 
	 * @return string html for fa_icon field
	 */
	protected function fa_icon() {

		/**
		 * We our own filter to alter the html of our input field
		 */
		add_filter( 'premise_field_input', array( $this, 'fa_icon_input' ) );

		/**
		 * call the input field. 
		 * 
		 * This will be alter due to our hook above
		 * 
		 * @var string
		 */
		$field = $this->input_field();

		/**
		 * Filter to alter the html on the icon select btn
		 *
		 * @since 1.2
		 *
		 * @premise-hook premise_field_icon_insert_btn do filter for button to show fa icon
		 */
		$field .= apply_filters( 'premise_field_icon_insert_btn', $this->btn_insert_icon, $this->field );

		/**
		 * Filter to alter the html on the icon remove button
		 *
		 * @since 1.2
		 *
		 * @premise-hook premise_field_icon_remove_btn do filter for button to hide fa icon
		 */
		$field .= apply_filters( 'premise_field_icon_remove_btn', $this->btn_remove_icon, $this->field );

		/**
		 * premise_field_fa_icon_html
		 *
		 * @since 1.2
		 *
		 * @premise-hook premise_field_fa_icon_html do filter for fa_icon field
		 */
		return apply_filters( 'premise_field_fa_icon_html', $field, $this->field, $this->type );
	}



	


	/**
	 * build our fa_icon input
	 *
	 * @since 1.2
	 * 
	 * @param  string $field the html for the field default
	 * @return string        the new html for the field
	 */
	public function fa_icon_input( $field ) {
		return str_replace( 'type="fa_icon"', 'type="text" class="premise-fa_icon"', $field );
	}




	/**
	 * Display the fa-icons for user to choose from
	 * 
	 * @return string html for fa icons
	 */
	public function fa_icons() {
		$icons = '<div class="premise-field-fa-icons-container" style="display:none;"><ul>';
		
		foreach ( (array) premise_get_fa_icons() as $icon ) {
			
			$icons .= '<li class="premise-field-fa-icon-li premise-inline-block premise-float-left">
				<a href="javascript:;" class="premise-field-fa-icon-anchor premise-block" data-icon="'.$icon.'">
					<i class="fa fa-fw '.$icon.'"></i>
				</a>
			</li>';

		}

		$icons .= '</ul></div>';

		return $icons;
	}




	/**
	 * build video field
	 *
	 * Right now this only returns a textarea with some classes added to it. 
	 * Eventually this should have options to search for video to embed and
	 * display the video belo or something.
	 *
	 * @since 1.2
	 */
	protected function video() {

		/**
		 * We our own filter to alter the html of our input field
		 */
		add_filter( 'premise_field_textarea', array( $this, 'video_textarea' ) );

		/**
		 * call the input field. 
		 * 
		 * This will be alter due to our hook above
		 * 
		 * @var string
		 */
		$field = $this->textarea();

		return $field;
	}





	/**
	 * Filter the textarea for video field
	 *
	 * @since 1.2 
	 * 
	 * @param  string $field html for textarea field
	 * @return string        new html
	 */
	public function video_textarea( $field ) {
		return str_replace( '<textarea', '<textarea data-type="video" class="premise-video"', $field );
	}





	/**
	 * build wp_color field
	 *
	 * Right now this only returns a textarea with some classes added to it. 
	 * Eventually this should have options to search for wp_color to embed and
	 * display the wp_color belo or something.
	 *
	 * @since 1.2
	 */
	protected function wp_color() {

		/**
		 * We our own filter to alter the html of our input field
		 */
		add_filter( 'premise_field_input', array( $this, 'wp_color_input' ) );

		/**
		 * call the input field. 
		 * 
		 * This will be alter due to our hook above
		 * 
		 * @var string
		 */
		$field = $this->input_field();

		return $field;
	}





	/**
	 * Filter the textarea for wp_color field
	 *
	 * @since 1.2 
	 * 
	 * @param  string $field html for textarea field
	 * @return string        new html
	 */
	public function wp_color_input( $field ) {
		return str_replace( 'type="wp_color"', 'type="text" data-type="wp_color" class="premise-wp_color"', $field );
	}




	/**
	 * try to get the option value for a field
	 *
	 * @since 1.2 added before but documented in this version
	 * 
	 * @param  string $name name attribute to know what option to look for
	 * @return mixed       returns the value found or an empty string if nothing was found
	 */
	protected function get_db_value() {
		
		if ( ! empty( $this->args['value'] ) ) {
			$val = $this->args['value'];
		}
		else {
			$name = ! empty( $this->args['name'] ) ? $this->args['name'] : $this->get_name();
			
			if ( empty( $name ) )
				return '';

			$context = ! empty( $this->args['context'] ) ? $this->args['context'] : '';

			$val = premise_get_value( $name, $context );
		}

		if ( $val ) 
			return esc_attr( $val );
		else 
			return ! empty( $this->field['default'] ) ? esc_attr( $this->field['default'] ) : '';
	}





	/**
	 * get value of a field by context
	 *
	 * Context applies to where the field is saved. By default fields are assumed to be safed
	 * in the options table. But if adding fields to a custom post type, get_option() would not work anymore
	 * so we pass the context 'post' and then Premise knows to get value from get_post_meta instead of get_option
	 *
	 * @since 1.2 
	 * 
	 * @return mixed value found
	 */
	protected function get_value_by_context( $name ) {
		$context = $this->args['context'];

		switch( $context ) {
			case 'post':
				$value = premise_get_option( $name, 'post' );
			break;

			default :
				return get_option( $this->args['name'] );
			break;
		}
	}




	/**
	 * Get id attribute for field from name
	 *
	 * @since 1.2 added before but documented in this version
	 * 
	 * @param  string $name string to get id from
	 * @return string       filtered string for id
	 */
	protected function get_id_att() {
		$id_att = '';

		if ( ! empty( $this->args['id'] ) ) {
			$id_att = $this->args['id'];
		}

		elseif ( ! empty( $this->args['name'] ) ) {
			$name = $this->args['name'];
			
			/**
			 * If values are stored in an array
			 */
			if ( preg_match( '/\[|\]/', $name ) ) {

				/**
				 * Turn html att name into an array of keys
				 *
				 * This will help us get the options from the database
				 *
				 * @var array
				 */
				$id_att = preg_replace( array('/\[/', '/\]/'), array('-', ''), $name );
			}
			else {
				$id_att = $name;
			}
		}

		return $this->args['id'] = esc_attr( $id_att );
	}




	/**
	 * Get the name attribute from the id
	 *
	 * @since 1.2 added before but documented in this version
	 * 
	 * @param  string $label string to get name attribute from
	 * @return string        filtered string for name
	 */
	protected function get_name() {
		$name = '';

		if ( ! empty( $this->args['name'] ) ) {
			$name = $this->args['name'];
		}

		elseif ( ! empty( $this->args['id'] ) ) {
			$name = $this->args['id'];
			$name = preg_replace('/[^-_a-z0-9]/', '', $name);
		}

		$name = ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) && ! preg_match('/\[\]$/', $this->args['name'] ) ? $name . '[]' : $name;

		return $this->args['name'] = esc_attr( $name );
	}




	/**
	 * Generate attributes for input field
	 *
	 * @since 1.2 
	 * 
	 * @param  string $k attribute name
	 * @param  string $v attribute value
	 * @return string    string of attributes
	 */
	protected function get_atts() {

		$field = ! empty( $this->field['attribute'] ) ? ' ' . $this->field['attribute'] : '';

		$_field = $this->field;

		if ( 'select' == $this->type || 'textarea' == $this->type )
			unset( $_field['value'] );

		if ( 'radio' == $this->type || 'checkbox' == $this->type )
			$field .= ! empty( $this->field['value_att'] ) && ! empty( $_field['value'] ) ? ' ' . checked( $this->field['value_att'], $_field['value'], false ) : '';

		unset( $_field['label'] );
		unset( $_field['tooltip'] );
		unset( $_field['add_filter'] );
		unset( $_field['template'] );
		unset( $_field['default'] );
		unset( $_field['options'] );
		unset( $_field['value_att'] );
		unset( $_field['attribute'] );
		unset( $_field['context'] ); 

		foreach ( $_field as $k => $v ) {
			$field .= ! empty( $v ) ? ' '.esc_attr( $k ).'="'.esc_attr( $v ).'"' : '';
		}
		
		return $field;
	}




	/**
	 * get the field wraper classes
	 * 
	 * @return string classes for field wrapper
	 */
	protected function get_wrapper_class() {

		// Start with the main class
		$class = 'premise-field';

		foreach( $this->field as $k => $v ) {
			$class .= ! empty( $v ) ? ' premise-field-' . esc_attr( $k ) : '';
		}

		return $class;
	}




	/**
	 * remove_filters
	 *
	 * After everything runs and we save our HTML for the field
	 * unhook the filters and action hooks
	 *
	 * @since 1.2 removes filters to avoid repetition
	 */
	protected function remove_filters() {
		foreach ( (array) $this->filters_used as $hook ) 
			remove_all_filters( $hook );
	}




	/**
	 * Get the field
	 * 
	 * @return string html for complete field
	 */
	public function get_field() {
		return $this->html;
	}




}
?>