<?php

/*
  Plugin Name: Sebaocano Custom Table Reader
  Description: Show your custom tables on the backend
  Version: 1.1
  Author: Seba Ocano
  Author URI: https://www.sebaocano.com
  Text Domain: sebaocanoctr
  Domain Path: /languages 
*/

class SebaocanoCTR{
  function __construct() {
    add_action('admin_menu', array($this, 'adminPage')); //Fires before the administration menu loads in the admin.
    add_action('admin_menu', array($this, 'ctrMenu')); //Fires before the administration menu loads in the admin.
    add_action('admin_init', array($this, 'settings')); //Fires as an admin screen or script is being initialized.
  }
  function adminPage() {
    add_options_page(
	'Custom Table Reader Settings', //Title of the admin page
	'Custom Table Reader', //title on the left menu on settings
	'manage_options', //permissions necesary to see this page, in this case only admins can see the page
	'SebaOcano-custom-table-reader', //slung of the page
	array($this, 'ourHTML') // name of the function that shows the HTML Code
	);
  }
  
  function ourHTML() { ?>
    <div class="wrap">
      <h1>Custom table reader settings</h1>
      <form action="options.php" method="POST">
      <?php
        settings_fields('CTR'); //name of the group on the register_setting
        do_settings_sections('SebaOcano-custom-table-reader');
        submit_button();
      ?>
      </form>
      
    </div>
  <?php }
  
  function ctrMenu() {
    add_menu_page(
	'Custom table reader', //Title of the page
	'CTR', //text that show up in the sidebar
	'manage_options', //permissions necesary to see this page, in this case only admins can see the page
	'SebaOcano-custom-table-reader-display', //slung of the page
	array($this, 'tableHTML'), // name of the function that shows the HTML Code
	'dashicons-database', //icon
	'100'
	);
  }
  
  function tableHTML() { ?>
    <div class="wrap">
      <h1>Custom table display</h1>
      
	  <?php 
		global $wpdb;
		$table_name=get_option('CTR_table_name');
		$order_by=get_option('CTR_order_by_field');
		$sortening=get_option('CTR_sortening');
		$pages=get_option('CTR_pages_number');
		$fields_to_show=get_option('CTR_fields_to_show');
		
		if(!isset($_GET['pagenumber'])){$page_number = 1; }else{$page_number = filter_var($_GET['pagenumber'], FILTER_SANITIZE_NUMBER_INT); }
		$limit = $pages;
		$initial_page = ($page_number-1) * $limit;
		//var_dump($limit);
		//echo "PAGE NUMBER: ".$page_number;
		
		if($fields_to_show=="all"){
			$fields_query = $wpdb->get_results( "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS  WHERE TABLE_SCHEMA = 'local' AND TABLE_NAME ='".$table_name."'; " );
			$fields=null;
			foreach($fields_query as $field){
				$fields.=$field->COLUMN_NAME.", ";
			}
			$fields=rtrim($fields, ", ");
			
			$total_rows = $wpdb->get_var("SELECT COUNT(*) FROM ".$table_name);
			$total_pages = ceil ($total_rows / $limit);
			
			$query = $wpdb->get_results( "SELECT ".$fields." FROM ".$table_name." ORDER BY ".$order_by." ".$sortening." ".$initial_page.','.$limit );
			
			
			
			
			
		}else{
			
			$total_rows = $wpdb->get_var("SELECT COUNT(*) FROM ".$table_name);
			$total_pages = ceil ($total_rows / $limit);
			
			$fields_to_show_sql=rtrim($fields_to_show, ",");
			$query = $wpdb->get_results( "SELECT ".$fields_to_show_sql." FROM ".$table_name." ORDER BY ".$order_by." ".$sortening." LIMIT ".$initial_page.','.$limit );
		}
		?>
      <h2>Current Query: </h2> 
      <?php 
	  if($fields_to_show=="all"){
	  	echo "SELECT ".$fields." FROM ".$table_name." ORDER BY ".$order_by." ".$sortening." LIMIT ".$initial_page.','.$limit; 
      }else{
	 	echo "SELECT ".$fields_to_show_sql." FROM ".$table_name." ORDER BY ".$order_by." ".$sortening." LIMIT ".$initial_page.','.$limit; 
      }?> 
       <table class="pet-adoption-table">
            <tr>
              <?php if($fields_to_show=="all"){
				  foreach($fields_query as $field){?>
                    <th><?php echo $field->COLUMN_NAME; ?></th>
                  <?php }?>
              <?php }else{
				$fields_query=explode(", ",$fields_to_show);
			  	//print_r($fields_query);
			  	foreach($fields_query as $field){?>
                    <th><?php echo rtrim($field, ","); ?></th>
                  <?php }?>
                
              <?php }?>
              
            </tr>
            <?php 
			foreach($query as $record) { ?>
			<tr>
				<?php foreach($fields_query as $field){?>
					<td>
					<?php 
					if($fields_to_show=="all"){
						$COLUMN_NAME=$field->COLUMN_NAME;
						echo $record->$COLUMN_NAME;
					}else{
						$field;
						$field=rtrim($field, ",");
						echo $record->$field;
					}
					?>
					</td>
				<?php }?>
			</tr>
			<?php }?>
            
          </table>
          
          <?php 
		  for($page_number = 1; $page_number<= $total_pages; $page_number++) {
				echo '<a href = "?page=SebaOcano-custom-table-reader-display&pagenumber=' . $page_number . '">' . $page_number . ' </a>';
		  }?>
    </div>
  <?php }
  
  
  
  
  
  function settings() {
	add_settings_section(
		'CTR_first_section', //name of the section, the same as used on the add_settings_field
		null, // title for the section
		null, // subtitle for the section
		'SebaOcano-custom-table-reader' //slug of the page where it will be shown
	);
	
	//PAGINATION NUMBER START
	add_settings_field(
		'CTR_pages_number', //name of the group that belongs, must be the same as the second parameter on register_settings
		'Number of records per page', //what user will see in the menu
		array($this, 'paginationHTML'), //name of the function that will show the HTML of this setting
		'SebaOcano-custom-table-reader', //slung of the page, must be the same used on the add_options_page, specificaly the fourth paramether
		'CTR_first_section' //section where this setting will be shown
	);
	
	register_setting( //needs to be used once per option
		'CTR', //name of the group that this settings belongs to
		'CTR_pages_number', //name of the specific setting when saved on the database
		array(
			'sanitize_callback' => 'sanitize_text_field', //sanitization callback
			'default' => '20' //default value of the setting
			) 
	); 
	//PAGINATION NUMBER END
	
	//TABLE NAME START
	add_settings_field(
		'CTR_table_name', //name of the group that belongs, must be the same as the second parameter on register_settings
		'Name of table to query', //what user will see in the menu
		array($this, 'tableNameHTML'), //name of the function that will show the HTML of this setting
		'SebaOcano-custom-table-reader', //slung of the page, must be the same used on the add_options_page, specificaly the fourth paramether
		'CTR_first_section' //section where this setting will be shown
	);
	
	register_setting( //needs to be used once per option
		'CTR', //name of the group that this settings belongs to
		'CTR_table_name', //name of the specific setting when saved on the database
		array(
			'sanitize_callback' => array($this, 'sanitize_table_name'), //sanitization callback
			'default' => 'none' //default value of the setting
			) 
	); 
	//TABLE NAME END
	
	//ORDER BY FIELD START
	add_settings_field(
		'CTR_order_by_field', //name of the group that belongs, must be the same as the second parameter on register_settings
		'Name of field to order by', //what user will see in the menu
		array($this, 'orderByHTML'), //name of the function that will show the HTML of this setting
		'SebaOcano-custom-table-reader', //slung of the page, must be the same used on the add_options_page, specificaly the fourth paramether
		'CTR_first_section' //section where this setting will be shown
	);
	
	register_setting( //needs to be used once per option
		'CTR', //name of the group that this settings belongs to
		'CTR_order_by_field', //name of the specific setting when saved on the database
		array(
			'sanitize_callback' => 'sanitize_text_field', //sanitization callback
			'default' => 'none' //default value of the setting
			) 
	); 
	//ORDER BY FIELD END
	
	//FIELDS TO SHOW START
	add_settings_field(
		'CTR_fields_to_show', //name of the group that belongs, must be the same as the second parameter on register_settings
		'Fields to show', //what user will see in the menu
		array($this, 'fieldsToShowHTML'), //name of the function that will show the HTML of this setting
		'SebaOcano-custom-table-reader', //slung of the page, must be the same used on the add_options_page, specificaly the fourth paramether
		'CTR_first_section' //section where this setting will be shown
	);
	
	register_setting( //needs to be used once per option
		'CTR', //name of the group that this settings belongs to
		'CTR_fields_to_show', //name of the specific setting when saved on the database
		array(
			'sanitize_callback' => 'sanitize_text_field', //sanitization callback
			'default' => 'none' //default value of the setting
			) 
	); 
	//FIELDS TO SHOW END
	
	//SORTENING START
	add_settings_field(
		'CTR_sortening', //name of the group that belongs, must be the same as the second parameter on register_settings
		'Sort by ASC or DESC', //what user will see in the menu
		array($this, 'sorteningHTML'), //name of the function that will show the HTML of this setting
		'SebaOcano-custom-table-reader', //slung of the page, must be the same used on the add_options_page, specificaly the fourth paramether
		'CTR_first_section' //section where this setting will be shown
	);
	
	register_setting( //needs to be used once per option
		'CTR', //name of the group that this settings belongs to
		'CTR_sortening', //name of the specific setting when saved on the database
		array(
			'sanitize_callback' => 'sanitize_text_field', //sanitization callback
			'default' => 'none' //default value of the setting
			) 
	); 
	//SORTENING END
	
  }
  
  
  function sanitize_table_name($input) { //input is the value that users enters
  
  	global $wpdb;
	$tables = $wpdb->get_results( "SELECT table_name FROM information_schema.tables WHERE table_type='BASE TABLE' AND table_schema = '".DB_NAME."'" );
	$valid='no';
	foreach($tables as $table){
		if($table->table_name==$input){$valid='yes';}
	}
	if($valid=='no'){
		add_settings_error(
			'CTR_table_name', //name of the setting
			'CTR_table_name_error', //name of the error
			'The table does not exist on the database'//errror message to the user
		);
		return get_option('CTR_table_name');
	}elseif($valid=='yes'){
		return $input;
	}
 }
  
  
  function paginationHTML() {?>
    <select name="CTR_pages_number">
    	<option <?php selected(get_option('CTR_pages_number'),  10)  ?> >10</option>
        <option <?php selected(get_option('CTR_pages_number'),  15)  ?> >15</option>
        <option <?php selected(get_option('CTR_pages_number'),  20)  ?> >20</option>
        <option <?php selected(get_option('CTR_pages_number'),  25)  ?> >25</option>
        <option <?php selected(get_option('CTR_pages_number'),  30)  ?> >30</option>
        <option <?php selected(get_option('CTR_pages_number'),  40)  ?> >40</option>
        <option <?php selected(get_option('CTR_pages_number'),  50)  ?> >50</option>
    </select>
	
	<?php 
  }
  
  function tableNameHTML() {?>
    <select name="CTR_table_name">
    	<?php 
		global $wpdb;
		$tables = $wpdb->get_results( "SELECT table_name FROM information_schema.tables WHERE table_type='BASE TABLE' AND table_schema = '".DB_NAME."'" );
		foreach($tables as $table){
	  	?>
        	<option <?php selected(get_option('CTR_table_name'), $table->table_name)  ?> ><?php echo $table->table_name;?></option>
        <?php } ?>
        
    </select>
	
	<?php 
  }
  
   function orderByHTML() {?>
    <select name="CTR_order_by_field">
    	<?php 
		global $wpdb;
		$fields = $wpdb->get_results( "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS  WHERE TABLE_SCHEMA = 'local' AND TABLE_NAME ='".get_option('CTR_table_name')."'; " );
	  	foreach($fields as $field){
	  	?>
        	<option value="<?php echo $field->COLUMN_NAME;?>" <?php selected(get_option('CTR_pages_number'), $field->table_name)  ?>>
				<?php echo $field->COLUMN_NAME;?> ( <?php echo $field->DATA_TYPE;?>)
            </option>
        <?php } ?>
        
    </select>
	
	<?php 
  }
  
   function sorteningHTML() {?>
    <select name="CTR_sortening">
    	<option>ASC</option>
        <option>DESC</option>
        
    </select>
	
	<?php 
  }
  
  function fieldsToShowHTML() {?>
    	<?php 
		global $wpdb;
		$fields = $wpdb->get_results( "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS  WHERE TABLE_SCHEMA = 'local' AND TABLE_NAME ='".get_option('CTR_table_name')."'; " );
		foreach($fields as $field){
		?>
			<input type="checkbox" id="field_<?php echo $field->COLUMN_NAME;?>" value="<?php echo $field->COLUMN_NAME;?>," class="table_fields" 
            <?php if(strpos(get_option('CTR_fields_to_show'), $field->COLUMN_NAME.",") !== false){?> checked <?php }?> >
			<label for="field_<?php echo $field->COLUMN_NAME;?>"><?php echo $field->COLUMN_NAME;?></label>
            <br>
		<?php } ?>
	<script>
    jQuery(document).ready(function($) {
		$(".table_fields").click(function() {
			var output = "";
			$("input:checked").each(function() {
			  output += $(this).val() + " ";
			});
			$("#CTR_fields_to_show").val(output.trim());
		  });
	})
	</script>
    
    
    <input type="hidden" name="CTR_fields_to_show" value="all" id="CTR_fields_to_show">
	<?php 
  }
  
  
  
  
  
  

  
}

$SebaocanoCTR = new SebaocanoCTR();


	
