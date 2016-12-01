<?php
class WP_Form_front {
    private $db;
    private $tags = array();
    private $actions = array();
    private $messages = array();
    public function __construct() {
        $this->db = \WeDevs\ORM\Eloquent\Database::instance();
    }
    
    public function init() {
        $actions = apply_filters('madeit_forms_actions', array());
        foreach($actions as $id => $value) {
            $this->addAction($id, $value);
        }
        
        $modules = apply_filters('madeit_forms_modules', array());
        foreach($modules as $id => $value) {
            $this->addModule($id, $value);
        }
        
        
        wp_register_style('madeit-form-style', MADEIT_FORM_URL . '/front/css/style.css', array(), null);
        wp_enqueue_style('madeit-form-style');
        
        $this->shortCodes();
    }
    
    public function shortCodes() {
        add_shortcode('form', [$this, 'shortcode_form']);
    }
    
    function shortcode_form($atts) {
        extract(shortcode_atts(array(
            'id' => 0
        ), $atts));
        ob_start();
        
        $form = $this->db->table('madeit_forms')->where('id', $id)->first();
        $formValue = $form->form;
        $formValue = str_replace('\"', '"', $formValue);
        if(isset($form->id)) {
            if(isset($_POST['form_id']) && $_POST['form_id'] == $id) {
                //validate input fields
                $error = false;
                $error_msg = "";
                $messages = json_decode($form->messages, true);
                
                //insert form input
                foreach($_POST as $k => $v) {
                    $tag = $this->getTagNameFromPostInput($formValue, $k);
                    if($tag !== false) {
                        if(is_callable($this->tags[$tag]['validation'])) {
                            $result = call_user_func($this->tags[$tag]['validation'], $this->getOptionsFromTag($formValue, $tag, $k), $v, $messages);
                            if($result !== true) {
                                $error = true;
                                $error_msg = $result;
                            }
                        }
                    }
                }
                
                if($error) {
                    echo '<div class="madeit-form-error">' . $error_msg . '</div>';
                    $content = ob_get_clean();
                    return $content;
                }
                
                //check spam
                $spam = false;
                
                
                //insert into DB
                $postData = $_POST;
                unset($postData['form_id']);
                $dbI = $this->db->table('madeit_form_inputs')->insert(array( 
                        'form_id' => $form->id,
                        'data' => json_encode($postData), 
                        'ip' => $this->getIP(),
                        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "UNKNOWN",
                        'spam' => $spam ? 1 : 0,
                        'read' => 0,
                        'result' => '',
                        'create_time' => date('Y-m-d H:i:s')
                    )
                );
                
                //execute actions
                if(isset($form->actions) && count($form->actions) > 0) {
                    $formActions = json_decode($form->actions, true);
                    foreach($formActions as $actID => $actionInfo) {
                        $action = $this->actions[$actionInfo['_id']];
                        
                        $data = array();
                        foreach($action['action_fields'] as $name => $info) {
                            $inputValue = isset($actionInfo[$name]) ? $actionInfo[$name] : $info['value'];
                            $data[$name] = $this->changeInputTag($inputValue);
                        }
                        
                        if(is_callable($action['callback'])) {
                            $result = call_user_func($action['callback'], $data, $messages);
                            if(is_array($result) && isset($result['type'])) {
                                if($result['type'] == "JS") {
                                    echo "<script>" . $result['code'] . "</script>";
                                }
                            } else if($result !== true) {
                                $error = true;
                                $error_msg = $result;
                            }
                        } else {
                            //can't execute action ...
                        }
                    }
                }
                
                
                //echo "<pre>" . print_r($dbI, true) . "</pre>";
                if($error) {
                    echo '<div class="madeit-form-error">' . $error_msg . '</div>';
                } else {
                    echo '<div class="madeit-form-success">' . $messages['success'] . '</div>';
                }
                //return success message
            } else {
                echo '<form action="" method="post">';
                echo '<input type="hidden" name="form_id" value="' . $id . '">';
                $formValue = $form->form;
                $formValue = str_replace('\"', '"', $formValue);
                echo do_shortcode($formValue);
                echo '</form>';
            }
        } else {
            echo __("Can't display the form.", 'forms-by-made-it');
        }
        
        $content = ob_get_clean();
        return $content;
    }
    
    private function changeInputTag($value) {
        foreach($_POST as $k => $v) {
            $value = str_replace("[" . $k . "]", $v, $value);
        }
        return $value;
    }
    
    private function getTagNameFromPostInput($form, $inputKey) {
        $pos = strpos($form, 'name="' . $inputKey . '"');
        if($pos !== false) {
            $tags = explode("[", substr($form, 0, $pos));
            if(count($tags) > 0) {
                $spaces = explode(" ", $tags[count($tags) - 1]);
                if(isset($spaces[0])) {
                    return $spaces[0];
                }
            }
        }
        return false;
    }
    
    private function getOptionsFromTag($form, $tag, $name) {
        $pos = strpos($form, 'name="' . $name . '"');
        if($pos !== false) {
            $firstPart = strpos($form, 0, $pos);
            $parts = explode("[" . $tag, $firstPart);
            if(count($parts) > 0) {
                $splitForm = explode("[" . $tag, $form);
                $partWithTag = $splitForm[count($parts)];
                $partWithTag = substr($partWithTag, 0, strpos($partWithTag, "]"));
                
                $key = "";
                $data = array();
                foreach(explode('="', $partWithTag) as $o) {
                    if($key == "") {
                        $space = explode(" ", $o);
                        if(count($space) <= 1) {
                            $key = $space[0];
                        } else {
                            $key = $space[count($space) - 1];
                        }
                    } else {
                        $data[$key] = substr($o, 0, strpos($o, '"'));
                        $key = trim(substr($o, strpos($o, '"') + 1));
                    }
                }
                return $data;
            }
        }
        return false;
    }
    
    public function addAction($id, $value) {
        $this->actions[$id] = $value;
        if(count($value['message_fields']) > 0) {
            $this->messages = array_merge($this->messages, $value['message_fields']);
        }
    }
    
    public function addModule($id, $value) {
        $this->tags[$id] = $value;
        if(count($value['message_fields']) > 0) {
            $this->messages = array_merge($this->messages, $value['message_fields']);
        }
    }
    
    public function getIP() {
        foreach(array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if(array_key_exists($key, $_SERVER) === true) {
                foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return "UNKNOWN";
    }
    
    public function addHooks() {
        add_action('init', array($this, 'init'));
    }
}