<?php
if(!class_exists("WP_MADEIT_FORM_Module")) {
    require_once(MADEIT_FORM_DIR . '/modules/WP_MADEIT_FORM_Module.php');
}
class WP_MADEIT_FORM_Module_Textarea extends WP_MADEIT_FORM_Module {
    public function __construct() {
        $this->addTag('textarea', __('Text area', 'forms-by-made-it'), 'tag_generator_textarea', array($this, 'tag_generator_textarea'), array($this, 'validation_text'));
        
        $this->addHooks();
    }
    
    public function tag_generator_textarea($contact_form, $args = '') {
        $args = wp_parse_args($args, array());
        $type = $args['id'];
        
        $description =__("Generate a form-tag for a multi-line text input field. For more details, see %s.", 'forms-by-made-it');
        $desc_link = '<a href="' . esc_url('https://www.madeit.be/wordpress/forms/docs/text-fields/') . '" target="_blank">' . __('Text Fields', 'forms-by-made-it') . '</a>';

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
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-name'); ?>"><?php echo esc_html(__('Name', 'forms-by-made-it')); ?></label></th>
                            <td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($args['content'] . '-name'); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-values'); ?>"><?php echo esc_html(__('Default value', 'forms-by-made-it')); ?></label></th>
                            <td>
                                <input type="text" name="values" class="oneline" id="<?php echo esc_attr($args['content'] . '-values'); ?>" /><br />
                                <label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html(__('Use this text as the placeholder of the field', 'forms-by-made-it')); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-id'); ?>"><?php echo esc_html(__('Id attribute', 'forms-by-made-it')); ?></label></th>
                            <td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($args['content'] . '-id'); ?>" /></td>
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
    
    public function validation_textarea($tagOptions, $value, $messages) {
        if($tagOptions !== false) {
            if(isset($tagOptions['required']) && $tagOptions['required'] == "yes" && strlen(trim($value)) == 0) {
                return $messages['invalid_required'] . " (" . $tagOptions['name'] . ")";
            }
        }
        return true;
    }
    
    public function textareaShortcode($atts) {
        extract(shortcode_atts(array(
            'name' => "",
            'required' => false,
            'id' => '',
            'class' => '',
            'placeholder' => '',
            'value' => '',
        ), $atts ));
        
        if(isset($_POST[$name])) {
            $value = $_POST[$name];
        }
        ob_start();
        ?>
        <textarea <?php if($name != "") { ?> name="<?php echo esc_html($name); ?>" <?php } ?>
               <?php if($value != "") { ?> value="<?php echo esc_html($value); ?>" <?php } ?>
               <?php if($id != "") { ?> id="<?php echo esc_html($id); ?>" <?php } ?>
               class="<?php echo esc_html( apply_filters('madeit_forms_module_class', $class, 'textarea') ); ?>"
               <?php if($placeholder != "") { ?> placeholder="<?php echo esc_html($placeholder); ?>" <?php } ?>
               <?php echo $required == 'yes' ? "required" : "";  ?>
                  ><?php if($value != "") { echo esc_textarea($value); } ?></textarea>
        <?php
        $content = ob_get_clean();
        return $content;
    }
    
    public function addHooks() {
        add_filter('madeit_forms_modules', array($this, 'getAction'));
        
        
        add_shortcode('textarea', [$this, 'textareaShortcode']);
    }
}