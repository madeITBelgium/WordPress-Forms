<?php

if (PHP_SAPI !== 'cli') {
    exit;
}

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function esc_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function get_post_meta($post_id, $key, $single)
{
    return $GLOBALS['test_meta_by_id'][$post_id][$key] ?? $GLOBALS['test_meta'][$key] ?? '';
}

function get_post($post_id)
{
    if (isset($GLOBALS['test_posts'])) {
        return $GLOBALS['test_posts'][$post_id] ?? null;
    }

    return $GLOBALS['test_post'] ?? (object) ['post_title' => '<img src=x onerror=alert(1)>'];
}

require dirname(__DIR__).'/admin/WP_MADEIT_FORM_admin.php';

class SecurityTestAdmin extends WP_MADEIT_FORM_admin
{
    public $fields = [];

    public function __construct()
    {
    }

    public function getInputFieldsOfForm($formId)
    {
        return $this->fields;
    }
}

$admin = new SecurityTestAdmin();
$payload = '<img src=x onerror=alert(1)>';
$checks = 0;
foreach ([[], ['alpha']] as $fields) {
    $admin->fields = $fields;
    foreach ([$payload, [$payload, 'ordinary'], [[$payload], 'ordinary'], "Line one\nLine two & more", null] as $value) {
        $GLOBALS['test_meta'] = ['form_id' => 1, 'data' => json_encode(['alpha' => $value])];
        ob_start();
        $admin->custom_ma_form_inputs_column('input_0', 2);
        $output = ob_get_clean();
        if (strpos($output, '<') !== false || (is_array($value) && strpos($output, '&lt;img') === false)) {
            throw new RuntimeException('Unsafe submission column output: '.$output);
        }
        $checks++;
    }
}
ob_start();
$admin->custom_ma_form_inputs_column('form', 2);
$output = ob_get_clean();
if ($output !== esc_html($payload)) {
    throw new RuntimeException('Unsafe form title output');
}
$checks++;

function esc_attr($value)
{
    return esc_html($value);
}
function esc_textarea($value)
{
    return esc_html($value);
}
function __($value, $domain = '')
{
    return $value;
}
function _x($value, $context, $domain = '')
{
    return $value;
}
function absint($value)
{
    return abs((int) $value);
}
function wp_unslash($value)
{
    return is_array($value) ? array_map('wp_unslash', $value) : stripslashes($value);
}
function wp_slash($value)
{
    return is_array($value) ? array_map('wp_slash', $value) : addslashes($value);
}
function wp_kses_post($value)
{
    return strip_tags($value, '<strong><em><a><br>');
}
function sanitize_textarea_field($value)
{
    return trim(strip_tags($value));
}
function map_deep($value, $callback)
{
    return is_array($value) ? array_map(function ($item) use ($callback) { return map_deep($item, $callback); }, $value) : $callback($value);
}
function apply_filters($name, $value, ...$args)
{
    return isset($GLOBALS['test_filters'][$name]) ? $GLOBALS['test_filters'][$name]($value, ...$args) : $value;
}
function add_filter(...$args)
{
}
function do_action(...$args)
{
}
function current_user_can($capability, ...$args)
{
    return $GLOBALS['test_admin'] ?? false;
}
function wp_is_post_revision($post_id)
{
    return false;
}
function wp_verify_nonce($nonce, $action)
{
    return $nonce === 'valid-'.$action;
}
function wp_create_nonce($action)
{
    return 'valid-'.$action;
}
function wp_nonce_field($action, $name, $referer = true)
{
    echo '<input name="'.esc_attr($name).'" value="'.esc_attr(wp_create_nonce($action)).'">';
}
function wp_json_encode($value, $flags = 0)
{
    return json_encode($value, $flags);
}
function get_post_type($post_id)
{
    return get_post($post_id)->post_type;
}
function get_posts($args)
{
    return [];
}
function shortcode_atts($defaults, $attributes)
{
    return array_merge($defaults, array_intersect_key($attributes, $defaults));
}
function parse_blocks($content)
{
    return $GLOBALS['test_blocks'] ?? [];
}
function update_post_meta($post_id, $key, $value)
{
    $GLOBALS['test_writes'][$post_id][$key] = is_string($value) ? wp_unslash($value) : $value;
}
function wp_insert_post($data)
{
    $GLOBALS['test_inserted'][] = $data;

    return 123;
}
function add_role(...$args)
{
}
function register_post_type($name, $args)
{
    $GLOBALS['test_post_types'][$name] = $args;
}

class SecurityTestExit extends RuntimeException
{
}
function wp_die($message = '', ...$args)
{
    throw new SecurityTestExit($message);
}
function wp_send_json($data, $status = 200)
{
    echo json_encode($data);
    wp_die();
}
function check_ajax_referer($action, $name)
{
    if (!wp_verify_nonce($_POST[$name] ?? '', $action)) {
        wp_die('Invalid nonce');
    }
}
class WP_Form_Spam_Protection
{
    public function __construct(...$args)
    {
    }

    public function isSpam($data)
    {
        return false;
    }
}
class SecurityTestSettings
{
    public function loadDefaultSettings()
    {
        return [];
    }
}
function security_check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $GLOBALS['checks']++;
}

require dirname(__DIR__).'/front/WP_Form_front.php';
require dirname(__DIR__).'/api/WP_MADEIT_FORMS_Rest.php';
require dirname(__DIR__).'/DataInit.php';

$front = new WP_Form_front(new SecurityTestSettings());
$GLOBALS['test_post'] = (object) ['ID' => 1, 'post_type' => 'ma_forms', 'post_status' => 'publish', 'post_title' => 'Contact', 'post_content' => '<input name="alpha"><textarea name="message"></textarea>'];
$GLOBALS['test_blocks'] = [
    ['blockName' => 'madeitforms/input-field', 'attrs' => ['name' => 'alpha']],
    ['blockName' => 'madeitforms/largeinput-field', 'attrs' => ['name' => 'message']],
    ['blockName' => 'madeitforms/multi-value-field', 'attrs' => ['name' => 'choices', 'values' => "One\nTwo"]],
];
$GLOBALS['test_meta'] = ['save_inputs' => 1, 'messages' => json_encode(['success' => 'Thank you']), 'actions' => '[]'];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_FILES = [];
$_COOKIE = [];
$_GET = [];

foreach (['ajax', 'post'] as $route) {
    foreach ([[], ['madeit_forms_nonce' => 'expired'], ['madeit_forms_nonce' => ['malformed']]] as $legacyFields) {
        $_POST = array_merge(['form_id' => '1', 'alpha' => $payload.'Hello', 'message' => "Line one\nLine two", 'choices' => ['One', 'Two'], 'unexpected' => $payload], $legacyFields);
        $GLOBALS['test_writes'] = [];
        $GLOBALS['test_inserted'] = [];
        ob_start();

        try {
            if ($route === 'ajax') {
                $front->submitAjaxForm();
            } else {
                echo $front->shortcode_form(['id' => 1]);
            }
        } catch (SecurityTestExit $exception) {
        }
        $output = ob_get_clean();
        security_check(count($GLOBALS['test_inserted']) === 1, $route.' submission requires a frontend nonce');
        $stored = json_decode($front->dbToEnter($GLOBALS['test_writes'][123]['data']), true);
        security_check(!isset($stored['unexpected']) && !isset($stored['madeit_forms_nonce']), 'Unexpected request values stored');
        security_check($stored['alpha'] === 'Hello' && $stored['choices'] === ['One', 'Two'] && $stored['message'] === "Line one\nLine two", 'Sanitized fields did not round trip');
        security_check(strpos($output, $payload) === false, 'Submission response reflected HTML');
    }
    foreach ([['alpha' => [$payload]], ['choices' => [[$payload]]]] as $invalidValues) {
        $_POST = array_merge(['form_id' => 1], $invalidValues);
        $GLOBALS['test_inserted'] = [];
        ob_start();

        try {
            if ($route === 'ajax') {
                $front->submitAjaxForm();
            } else {
                echo $front->shortcode_form(['id' => 1]);
            }
        } catch (SecurityTestExit $exception) {
        }
        ob_end_clean();
        security_check(!$GLOBALS['test_inserted'], 'Malformed input reached storage');
    }
    foreach (['GET', 'PUT'] as $method) {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_POST = ['form_id' => 1, 'alpha' => 'Hello'];
        $GLOBALS['test_inserted'] = [];
        ob_start();

        try {
            if ($route === 'ajax') {
                $front->submitAjaxForm();
            } else {
                echo $front->shortcode_form(['id' => 1]);
            }
        } catch (SecurityTestExit $exception) {
        }
        ob_end_clean();
        security_check(!$GLOBALS['test_inserted'], 'Non-POST submission reached storage');
    }
    $_SERVER['REQUEST_METHOD'] = 'POST';
}

foreach (['draft', 'private', 'trash'] as $status) {
    $GLOBALS['test_post']->post_status = $status;
    $GLOBALS['test_inserted'] = [];
    ob_start();

    try {
        $front->submitAjaxForm();
    } catch (SecurityTestExit $exception) {
    }
    echo $front->shortcode_form(['id' => 1]);
    ob_end_clean();
    security_check(!$GLOBALS['test_inserted'], 'Unpublished form accepted a submission');
}
$GLOBALS['test_post']->post_status = 'publish';

foreach (['get', 'post'] as $source) {
    $_POST = [];
    $_GET = [];
    $values = ['alpha' => '\"><img src=x onerror=alert(1)>', 'message' => '</textarea><img src=x onerror=alert(1)>'];
    if ($source === 'get') {
        $_GET = $values;
    } else {
        $_POST = $values;
    }
    $html = $front->shortcode_form(['id' => 1]);
    security_check(strpos($html, 'madeit_forms_nonce') === false, 'Frontend nonce still rendered');
    $document = new DOMDocument();
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    security_check($document->getElementsByTagName('img')->length === 0, 'Reflected HTML created an element');
    security_check($document->getElementsByTagName('textarea')->item(0)->textContent === $values['message'], 'Textarea value did not round trip');
}

foreach ([false, true] as $isAdmin) {
    $GLOBALS['test_admin'] = $isAdmin;
    $rest = new WP_MADEIT_FORMS_Rest();
    security_check($rest->can_access() === $isAdmin, 'REST authorization failed');
    foreach (['', 'wrong', ['malformed'], 'valid-madeit_forms_save_1'] as $nonce) {
        $_POST = ['madeit_form_editor' => 'yes', 'form' => 'example', 'madeit_forms_admin_nonce' => $nonce];
        $GLOBALS['test_writes'] = [];
        $admin->save_form(1, $GLOBALS['test_post'], true);
        $admin->save_meta(1, $GLOBALS['test_post'], true);
        security_check(!empty($GLOBALS['test_writes']) === ($isAdmin && $nonce === 'valid-madeit_forms_save_1'), 'Form save authorization failed');
    }
}
$GLOBALS['test_admin'] = false;
$_POST = ['id' => 1];
$denied = false;

try {
    $admin->resendMail();
} catch (SecurityTestExit $exception) {
    $denied = true;
}
security_check($denied, 'Unauthorized mail resend allowed');
$GLOBALS['test_admin'] = true;
$GLOBALS['test_post']->post_type = 'ma_form_inputs';
$denied = false;

try {
    $admin->resendMail();
} catch (SecurityTestExit $exception) {
    $denied = true;
}
security_check($denied, 'Mail resend without nonce allowed');
$registration = new DataInit();
$registration->create_post_type();
security_check($GLOBALS['test_post_types']['ma_form_inputs']['show_in_rest'] === false, 'Submissions exposed in REST');
foreach ($GLOBALS['test_post_types'] as $definition) {
    security_check(count(array_unique($definition['capabilities'])) === 1 && $definition['capabilities']['edit_posts'] === 'manage_options', 'Post type capabilities allow non-administrators');
}
function esc_url_raw($url, $protocols = ['http', 'https'])
{
    $scheme = parse_url($url, PHP_URL_SCHEME);

    return $scheme && !in_array(strtolower($scheme), $protocols, true) ? '' : $url;
}
function esc_url($url)
{
    return esc_attr(esc_url_raw($url));
}
require dirname(__DIR__).'/actions/WP_MADEIT_FORM_Action.php';
foreach (['Redirect', 'Download', 'GAEvent', 'GAAdsEvent', 'Javascript'] as $action) {
    require dirname(__DIR__).'/actions/'.$action.'.php';
}
$attack = "');window.securityProbe=1;//</script><img src=x onerror=alert(1)>";
foreach ([
    ['WP_MADEIT_FORM_Redirect', ['redirect_url' => 'https://example.test/'.$attack]],
    ['WP_MADEIT_FORM_Download', ['download_url' => 'https://example.test/'.$attack]],
    ['WP_MADEIT_FORM_GAEvent', ['ga_event_category' => $attack, 'ga_event_action' => $attack, 'ga_event_label' => $attack]],
    ['WP_MADEIT_FORM_GAAdsEvent', ['ga_ads_event_code' => $attack, 'ga_ads_event_send_to' => $attack]],
] as $case) {
    $instance = (new ReflectionClass($case[0]))->newInstanceWithoutConstructor();
    $result = $instance->callback($case[1], [], []);
    security_check(strpos($result['code'], '<img') === false && strpos($result['code'], "');window.securityProbe") === false, 'Action script injection: '.$case[0]);
}
foreach (['WP_MADEIT_FORM_Redirect' => 'redirect_url', 'WP_MADEIT_FORM_Download' => 'download_url'] as $class => $field) {
    $instance = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    $result = $instance->callback([$field => 'javascript:alert(1)'], [], []);
    security_check(strpos($result['code'], 'javascript:') === false, 'Unsafe URL scheme accepted');
}
$instance = (new ReflectionClass('WP_MADEIT_FORM_Javacript'))->newInstanceWithoutConstructor();
$result = $instance->callback(['js_event_code' => $attack], [], ['js_event_code' => '<script>window.fixedEvent=true;</script>']);
security_check($result['code'] === '<script>window.fixedEvent=true;</script>', 'Custom JavaScript used submitted values');
function wp_remote_post($url, $args)
{
    return $GLOBALS['test_response'];
}
function wp_remote_retrieve_response_code($response)
{
    return $response['status'];
}
function wp_remote_retrieve_body($response)
{
    return $response['body'];
}
function is_wp_error($response)
{
    return $response instanceof RuntimeException;
}
class SecurityTestCaptchaSettings
{
    public function loadDefaultSettings()
    {
        return ['reCaptcha' => ['enabled' => true, 'secret' => 'test', 'key' => 'test', 'version' => 'V3', 'minScore' => 0.5]];
    }
}
$captchaFront = new WP_Form_front(new SecurityTestCaptchaSettings());
$GLOBALS['test_post']->post_type = 'ma_forms';
$_GET = [];
$_POST = ['form_id' => '1', 'g-recaptcha-response' => 'test&response=other', 'alpha' => 'Hello'];
foreach ([
    [new RuntimeException('Network error'), false],
    [['status' => 500, 'body' => '{}'], false],
    [['status' => 200, 'body' => 'invalid json'], false],
    [['status' => 200, 'body' => '{"success":false,"score":1}'], false],
    [['status' => 200, 'body' => '{"success":true}'], false],
    [['status' => 200, 'body' => '{"success":true,"score":0.1}'], false],
    [['status' => 200, 'body' => '{"success":true,"score":0.9}'], true],
] as $case) {
    $GLOBALS['test_response'] = $case[0];
    foreach (['ajax', 'post'] as $route) {
        $GLOBALS['test_inserted'] = [];
        ob_start();

        try {
            if ($route === 'ajax') {
                $captchaFront->submitAjaxForm();
            } else {
                echo $captchaFront->shortcode_form(['id' => 1]);
            }
        } catch (SecurityTestExit $exception) {
        }
        ob_end_clean();
        security_check(!empty($GLOBALS['test_inserted']) === $case[1], 'CAPTCHA verification failed for '.$route);
    }
}
$unicodeData = ['alpha' => "Quoted \"value\" and caf\u{00e9} and literal u1234", 'message' => "Line one\nLine two\\folder"];
foreach ([$front, $admin] as $codec) {
    $stored = wp_unslash(wp_slash($codec->enterToDB(wp_json_encode($unicodeData))));
    security_check(json_decode($codec->dbToEnter($stored), true) === $unicodeData, 'Unicode JSON round trip failed');
}
$csv = new ReflectionMethod(WP_MADEIT_FORM_admin::class, 'csv_value');
foreach (['=1+1', '+1', '-1', '@SUM(1)', "\t=1", "\n=1", '  =1'] as $formula) {
    security_check($csv->invoke($admin, $formula) === "'".$formula, 'Unsafe spreadsheet formula');
}
security_check($csv->invoke($admin, 'ordinary value') === 'ordinary value', 'CSV text changed');
$realAdmin = (new ReflectionClass(WP_MADEIT_FORM_admin::class))->newInstanceWithoutConstructor();
$GLOBALS['test_meta']['form'] = '[text name="alpha"][email name="email"]';
security_check($realAdmin->getInputFieldsOfForm(1) === ['alpha', 'email'], 'Classic field lookup failed');

function wp_safe_remote_request($url, $args)
{
    $GLOBALS['test_http_args'] = $args;

    return $GLOBALS['test_response'];
}
function wp_safe_remote_post($url, $args)
{
    return wp_safe_remote_request($url, $args);
}
function wp_rand($min, $max)
{
    return $min;
}
require dirname(__DIR__).'/actions/Webhook.php';
require dirname(__DIR__).'/actions/Odoo.php';
require dirname(__DIR__).'/actions/ActiveCampaign.php';
$webhook = (new ReflectionClass(WP_MADEIT_FORM_Webhook::class))->newInstanceWithoutConstructor();
foreach ([new RuntimeException('Unsafe URL'), ['status' => 302, 'body' => ''], ['status' => 500, 'body' => ''], ['status' => 204, 'body' => '']] as $response) {
    $GLOBALS['test_response'] = $response;
    $result = $webhook->callback(['wh_url' => 'https://example.test/', 'wh_type' => 'POST', 'wh_body' => '{}'], ['action_wh_error' => 'failed'], [], 1, 123, []);
    security_check(($result === true) === (is_array($response) && $response['status'] === 204), 'Webhook response handling failed');
    security_check($GLOBALS['test_http_args']['redirection'] === 0 && $GLOBALS['test_http_args']['sslverify'] === true, 'Webhook network protections missing');
}
$odoo = (new ReflectionClass(WP_MADEIT_FORM_Odoo::class))->newInstanceWithoutConstructor();
$request = new ReflectionMethod($odoo, 'requestJsonRpc');
$GLOBALS['test_response'] = ['status' => 200, 'body' => '{"result":17}'];
security_check($request->invoke($odoo, 'https://example.test', []) === 17, 'Odoo JSON response failed');
security_check($GLOBALS['test_http_args']['redirection'] === 0 && $GLOBALS['test_http_args']['sslverify'] === true, 'Odoo network protections missing');
$campaign = (new ReflectionClass(WP_MADEIT_FORM_ActiveCampaign::class))->newInstanceWithoutConstructor();
$request = new ReflectionMethod($campaign, 'requestAC');
security_check($request->invoke($campaign, 'POST', 'https://example.test', 'test', []) === ['{"result":17}', 200], 'ActiveCampaign response failed');
security_check($GLOBALS['test_http_args']['redirection'] === 0 && $GLOBALS['test_http_args']['sslverify'] === true, 'ActiveCampaign network protections missing');
function current_time($type)
{
    return 1700000000;
}
function date_i18n($format, $timestamp)
{
    return gmdate($format, $timestamp);
}
function home_url()
{
    return 'https://example.test';
}
function wp_parse_url($url)
{
    return parse_url($url);
}

$replayCalls = [];
$definition = [
    'title'         => 'Email',
    'action_fields' => [
        'to'      => ['value' => 'default@example.test'],
        'message' => ['value' => 'Hello [alpha]'],
    ],
    'callback' => function ($data, $messages, $actionInfo, $formId, $inputId, $postData) use (&$replayCalls) {
        $replayCalls[] = compact('data', 'messages', 'actionInfo', 'formId', 'inputId', 'postData');

        return true;
    },
];
$registry = ['EMAIL' => $definition, 'WEBHOOK' => array_merge($definition, ['title' => 'Webhook'])];
$registry['FAILURE'] = array_merge($definition, ['callback' => function () { return 'Provider rejected request'; }]);
$registry['EXCEPTION'] = array_merge($definition, ['callback' => function () { throw new RuntimeException('secret credential'); }]);
$registry['CUSTOM_BROWSER'] = array_merge($definition, ['execution_context' => 'browser']);
$configured = [
    ['_id' => 'EMAIL', 'to' => '[email]', 'message' => '{{if alpha}}Hello [alpha]{{endif}} {{foreach choices}}[choices];{{endforeach}} {{DATUM}}'],
    ['_id' => 'FAILURE'],
    ['_id' => 'EXCEPTION'],
    ['_id' => 'WEBHOOK'],
    ['_id' => 'MISSING'],
    ['_id' => 'CUSTOM_BROWSER'],
];
foreach (['REDIRECT', 'DOWNLOAD', 'JS_EVENT', 'GA_EVENT', 'GA_ADS_EVENT'] as $browserAction) {
    $registry[$browserAction] = $definition;
    $configured[] = ['_id' => $browserAction];
}
$GLOBALS['test_filters']['madeit_forms_actions'] = function ($actions) use ($registry) { return $registry; };
$GLOBALS['test_filters']['madeit_forms_submit_actions'] = function ($actions) {
    $actions[] = ['_id' => 'EMAIL', 'message' => 'Filtered action'];

    return $actions;
};
$GLOBALS['test_filters']['madeit_forms_action_data'] = function ($data, $formId, $inputId, $actionInfo, $postData) {
    $data['filter_context'] = [$formId, $inputId, $postData['alpha']];

    return $data;
};
$savedData = ['alpha' => 'Stored visitor', 'email' => 'visitor@example.test', 'choices' => ['One', 'Two']];
$form = (object) ['ID' => 1, 'post_type' => 'ma_forms'];
$_POST = ['alpha' => 'Untrusted request value'];
$results = $front->replayServerActions($form, 123, $savedData, $configured, ['failed' => 'Configured failure']);
security_check(count($replayCalls) === 3, 'Server actions or action filter not executed');
security_check(count(array_filter($results, function ($result) { return $result['status'] === 'skipped'; })) === 6, 'Browser actions were not skipped');
security_check(array_column(array_slice($results, 0, 5), 'status') === ['success', 'failed', 'failed', 'success', 'failed'], 'Failure did not preserve later action execution');
security_check(strpos(json_encode($results), 'secret credential') === false, 'Exception exposed integration credentials');
security_check($replayCalls[0]['data']['to'] === 'visitor@example.test', 'Email recipient not resolved from saved input');
security_check($replayCalls[0]['data']['message'] === 'Hello Stored visitor One;Two; '.gmdate('j F Y', 1700000000), 'Replay template processing differs from submissions');
security_check($replayCalls[0]['data']['filter_context'] === [1, 123, 'Stored visitor'], 'Action data filter context missing');
security_check($replayCalls[0]['data']['id'] === 123 && $replayCalls[0]['formId'] === 1 && $replayCalls[0]['inputId'] === 123, 'Replay IDs are incorrect');
security_check($replayCalls[0]['postData'] === array_merge($savedData, ['input_id' => 123]), 'Saved input context not passed to callback');
security_check($replayCalls[1]['data']['to'] === 'default@example.test', 'Action defaults not applied');
security_check($replayCalls[0]['messages']['failed'] === 'Configured failure', 'Form messages not passed to actions');

unset($GLOBALS['test_filters']['madeit_forms_submit_actions']);
$GLOBALS['test_admin'] = true;
$GLOBALS['test_posts'] = [1 => $form, 123 => (object) ['ID' => 123, 'post_type' => 'ma_form_inputs', 'post_date' => '2026-09-29 12:00:00']];
$GLOBALS['test_meta_by_id'] = [
    123 => ['form_id' => 1, 'data' => $front->enterToDB(wp_json_encode($savedData))],
    1   => ['actions' => wp_json_encode([['_id' => 'EMAIL'], ['_id' => 'WEBHOOK'], ['_id' => 'REDIRECT']]), 'messages' => '{}'],
];
$replayAdmin = new WP_MADEIT_FORM_admin(new SecurityTestSettings());
foreach (['success', 'empty', 'invalid_data', 'invalid_actions', 'missing_form', 'wrong_type', 'get', 'denied', 'invalid_nonce'] as $scenario) {
    $replayCalls = [];
    $GLOBALS['test_inserted'] = [];
    $GLOBALS['test_writes'] = [];
    $originalMeta = $GLOBALS['test_meta_by_id'];
    $originalPosts = $GLOBALS['test_posts'];
    $_POST = ['id' => 123, 'nonce' => 'valid-ma_forms_resend_mail_123', 'alpha' => 'Forged'];
    $_SERVER['REQUEST_METHOD'] = $scenario === 'get' ? 'GET' : 'POST';
    $GLOBALS['test_admin'] = $scenario !== 'denied';
    if ($scenario === 'invalid_nonce') {
        $_POST['nonce'] = 'wrong';
    }
    if ($scenario === 'invalid_data') {
        $GLOBALS['test_meta_by_id'][123]['data'] = '{invalid';
    }
    if ($scenario === 'invalid_actions') {
        $GLOBALS['test_meta_by_id'][1]['actions'] = '{invalid';
    }
    if ($scenario === 'empty') {
        $GLOBALS['test_meta_by_id'][1]['actions'] = '[]';
    }
    if ($scenario === 'missing_form') {
        unset($GLOBALS['test_posts'][1]);
    }
    if ($scenario === 'wrong_type') {
        $GLOBALS['test_posts'][123] = (object) ['post_type' => 'post'];
    }
    ob_start();

    try {
        $replayAdmin->resendMail();
    } catch (SecurityTestExit $exception) {
    }
    $response = json_decode(ob_get_clean(), true);
    security_check(count($replayCalls) === ($scenario === 'success' ? 2 : 0), 'Replay handler guard failed: '.$scenario);
    security_check(!$GLOBALS['test_inserted'] && !$GLOBALS['test_writes'], 'Replay modified stored submissions: '.$scenario);
    if ($scenario === 'success') {
        security_check($response['success'] === true && array_column($response['results'], 'status') === ['success', 'success', 'skipped'], 'Replay handler result reporting failed');
        security_check($replayCalls[0]['data']['message'] === 'Hello Stored visitor', 'Handler used request values instead of saved data');
    }
    if ($scenario === 'empty') {
        security_check($response['results'] === [] && $response['message'] === 'No actions configured.', 'Empty action configuration not reported');
    }
    $GLOBALS['test_meta_by_id'] = $originalMeta;
    $GLOBALS['test_posts'] = $originalPosts;
}
ob_start();
$replayAdmin->ma_form_inputs_data($GLOBALS['test_posts'][123]);
$replayHtml = ob_get_clean();
security_check(strpos($replayHtml, 'Run actions again') !== false && strpos($replayHtml, 'window.confirm') !== false, 'Replay control or duplicate-side-effect confirmation missing');
security_check(strpos($replayHtml, '.text(result.title') !== false, 'Replay results are not rendered as text');
echo $checks." security regression checks passed.\n";
