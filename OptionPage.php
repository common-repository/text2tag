<?php /*
	This file is part of Text2Tag.

    'Text2Tag' is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    'Text2Tag' is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with 'Text2Tag'.  If not, see <http://www.gnu.org/licenses/>.
	
	Note that this software makes use of other libraries that are published under their own
	license. This program in no way tends to violate the licenses under which these 
	libraries are published.
	
	*/
?>
<div class="wrap">

<h2><?php _e('Text2Tag options') ?></h2>

<form method="post" action="options.php">
	<?php settings_fields("text2tag"); ?>
	 
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Taxonomy') ?></th>
			<td>
				<select name="text2tag_taxonomy">
					<?php
						$taxs = get_object_taxonomies('post');
						$default = get_option("text2tag_taxonomy", "words");
						
						foreach($taxs as $taxId)
						{
							$tax = get_taxonomy($taxId);
							
							if($tax->name == $default)
								$selected = "selected='selected'";
							else
								$selected = "";

							echo "<option $selected value='$tax->name'>" . ($tax->label?$tax->label:$tax->name) . "</option>";
						}
						
						if(!in_array("words", $taxs))
						{
							if($default == "words")
								$selected = "selected='selected'";
							else
								$selected = "";
							
							echo "<option $selected value='words'>" . __("Words") . "</option>";
						}
					?>
				</select>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><?php _e('Ignore Numbers') ?></th>
			<td>
				 <input type="checkbox" name="text2tag_nonumbers" value="1" <?php checked('1',get_option("text2tag_nonumbers")); ?> />
                 <label>Ignore numbers in the title and post content.</label>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><?php _e('Stop Words') ?></th>
			<td>
				<textarea class="large-text" rows="10" name="text2tag_stopwords"><?php echo get_option('text2tag_stopwords'); ?></textarea>
				<p>
					Most used words: 
					<?php
						$tax = get_option("text2tag_taxonomy", "words");
						$terms = get_terms($tax, array("number"=>20, 'orderby'=>"count", "order"=>"DESC", "fields"=>"names"));
						echo implode(", ", $terms); 
					?> 
				</p>
			</td>
		</tr>
	
	</table>
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
</form>

</div>

