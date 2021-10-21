<?php
if(!class_exists("WP_MADEIT_FORM_Module")) {
    require_once(MADEIT_FORM_DIR . '/modules/WP_MADEIT_FORM_Module.php');
}
class WP_MADEIT_FORM_Module_Select extends WP_MADEIT_FORM_Module {
    public function __construct() {
        $this->addTag('select', __('Drop-down menu', 'forms-by-made-it'), 'tag_generator_select', array($this, 'tag_generator_select'), array($this, 'validation_select'));

        $this->addHooks();
    }
    
    public function tag_generator_select($contact_form, $args = '') {
        $args = wp_parse_args( $args, array() );
        $type = $args['id'];
        
        $description = __("Generate a form-tag for a drop-down menu. For more details, see %s.", 'forms-by-made-it');
        $desc_link = '<a href="' . esc_url('https://www.madeit.be/wordpress/forms/docs/drop-down/') . '" target="_blank">' . __('Text Fields', 'forms-by-made-it') . '</a>';

        ?>
        <div class="control-box">
            <fieldset>
                <legend><?php echo sprintf(esc_html($description), $desc_link); ?></legend>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo esc_html(__('Field type', 'forms-by-made-it')); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php echo esc_html(__('Field type', 'forms-by-made-it')); ?></legend>
                                    <label><input type="checkbox" name="required" /> <?php echo esc_html(__('Required field', 'forms-by-made-it')); ?></label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-name' ); ?>"><?php echo esc_html(__('Name', 'forms-by-made-it')); ?></label></th>
                            <td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($args['content'] . '-name'); ?>" /></td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php echo esc_html(__('Options', 'forms-by-made-it')); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php echo esc_html(__('Options', 'forms-by-made-it')); ?></legend>
                                    <textarea name="values" class="values" id="<?php echo esc_attr($args['content'] . '-values'); ?>"></textarea>
                                    <label for="<?php echo esc_attr($args['content'] . '-values'); ?>"><span class="description"><?php echo esc_html(__("One option per line.", 'forms-by-made-it')); ?></span></label><br />
                                    <label><input type="checkbox" name="multiple" class="option" /> <?php echo esc_html(__('Allow multiple selections', 'forms-by-made-it')); ?></label><br />
                                    <label><input type="checkbox" name="include_blank" class="option" /> <?php echo esc_html(__('Insert a blank item as the first option', 'forms-by-made-it')); ?></label>
                                </fieldset>
                            </td>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-id'); ?>"><?php echo esc_html(__('Id attribute', 'forms-by-made-it')); ?></label></th>
                            <td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($args['content'] . '-id' ); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-class'); ?>"><?php echo esc_html(__('Class attribute', 'forms-by-made-it')); ?></label></th>
                            <td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr($args['content'] . '-class'); ?>" /></td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </div>
        <div class="insert-box">
            <input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />
            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'forms-by-made-it')); ?>" />
            </div>
            <br class="clear" />
            <p class="description mail-tag">
                <label for="<?php echo esc_attr($args['content'] . '-mailtag'); ?>">
                    <?php echo sprintf(esc_html(__("To use the value input through this field in a action field, you need to insert the corresponding name-tag (%s) into the field on the Actions tab.", 'forms-by-made-it')), '<strong><span class="mail-tag"></span></strong>'); ?>
                    <input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr($args['content'] . '-mailtag'); ?>" />
                </label>
            </p>
        </div>
        <?php
    }
    
    public function validation_select($tagOptions, $value, $messages) {
        if($tagOptions !== false) {
            if(isset($tagOptions['required']) && $tagOptions['required'] == "yes" && strlen(trim($value)) == 0) {
                return $messages['invalid_required'] . " (" . $tagOptions['name'] . ")";
            }
        }
        return true;
    }
    
    public function selectShortcode($atts) {
        extract(shortcode_atts(array(
            'name' => "",
            'required' => 'no',
            'multiple' => 'no',
            'include_blank' => 'no',
            'id' => '',
            'class' => '',
            'placeholder' => '',
            'value' => '',
        ), $atts ));
        
        $selected = null;
        if(isset($_POST[$name])) {
            $selected = $_POST[$name];
        }
        ob_start();
        ?>
        <select 
            <?php if($name != "") { ?> name="<?php echo esc_html($name); ?>" <?php } ?>
            <?php if($id != "") { ?> id="<?php echo esc_html($id); ?>" <?php } ?>
            class="<?php echo esc_html( apply_filters('madeit_forms_module_class', $class, 'select') ); ?>"
            <?php echo $required == 'yes' ? "required" : "";  ?>
            <?php echo $multiple == 'yes' ? "multiple" : "";  ?>
                >
            <?php
            if($include_blank == 'yes') {
                echo '<option value=""></option>';
            }
            foreach(explode("|", $value) as $v) {
                ?>
                <option value="<?php echo $v; ?>" <?php if($selected === $v) { echo 'SELECTED'; } ?>><?php echo $v; ?></option>
                <?php
            }
            ?>
        </select>
        <?php
        $content = ob_get_clean();
        return $content;
    }
    
    public function addHooks() {
        add_filter('madeit_forms_modules', array($this, 'getAction'));
        
        
        add_shortcode('select', [$this, 'selectShortcode']);
    }
}