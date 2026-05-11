<?php

class WP_Form_Spam_Protection
{
    private $getIpCallback;

    public function __construct($getIpCallback = null)
    {
        $this->getIpCallback = $getIpCallback;
    }

    public function isSpam($data)
    {
        $analysis = $this->analyze($data);

        return $analysis['spam'];
    }

    public function analyze($data)
    {
        $score = 0;
        $reasons = [];

        $ip = $this->getIP();
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? trim((string) $_SERVER['HTTP_USER_AGENT']) : '';

        $spamIPs = apply_filters('madeit_forms_spam_ips', []);
        if ($ip !== 'UNKNOWN' && in_array($ip, $spamIPs, true)) {
            $score += 100;
            $reasons[] = 'blacklisted_ip';
        }

        $spamUserAgents = apply_filters('madeit_forms_spam_user_agents', [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:61.0) Gecko/20100101 Firefox/61.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36 OPR/89.0.4447.51',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.170 Safari/537.36 OPR/53.0.2907.99',
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.170 Safari/537.36 OPR/53.0.2907.106',
            'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:102.0) Gecko/20100101 Firefox/102.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.170 Safari/537.36 OPR/53.0.2907.68',
        ]);
        if ($userAgent !== '' && in_array($userAgent, $spamUserAgents, true)) {
            $score += 90;
            $reasons[] = 'blocked_user_agent';
        }

        if ($userAgent === '' || strlen($userAgent) < 12) {
            $score += 20;
            $reasons[] = 'missing_or_short_user_agent';
        }

        if ($userAgent !== '' && preg_match('/(curl|wget|python-requests|httpclient|java|scrapy|bot|crawler|spider)/i', $userAgent)) {
            $score += 35;
            $reasons[] = 'suspicious_user_agent';
        }

        $honeypotField = (string) apply_filters('madeit_forms_spam_honeypot_field', 'madeit_website');
        if (isset($data[$honeypotField]) && trim((string) $data[$honeypotField]) !== '') {
            $score += 100;
            $reasons[] = 'honeypot_filled';
        }

        $renderedAtField = (string) apply_filters('madeit_forms_spam_rendered_at_field', 'madeit_form_rendered_at');
        $minimumSubmitSeconds = max(0, (int) apply_filters('madeit_forms_spam_min_submit_seconds', 2));
        if (isset($data[$renderedAtField])) {
            $renderedAt = (int) $data[$renderedAtField];
            if ($renderedAt > 0) {
                $submitAgeSeconds = time() - $renderedAt;
                if ($submitAgeSeconds < $minimumSubmitSeconds) {
                    $score += 15;
                    $reasons[] = 'submitted_too_fast';
                }
            } else {
                $score += 20;
                $reasons[] = 'invalid_render_time';
            }
        }

        $maxLinks = max(0, (int) apply_filters('madeit_forms_spam_max_links', 3));
        $spamWords = apply_filters('madeit_forms_spam_words', [
            'mail.ru',
            'yandex.ru',
            'bit.ly',
            'tinyurl.com',
            't.me/',
            'telegram.me/',
            'viagra',
            'cialis',
            'levitra',
            'porn',
            'sex cam',
            'escort',
            'casino',
            'betting',
            'poker',
            'payday loan',
            'quick loan',
            'forex',
            'binary options',
            'crypto investment',
            'guest post',
            'backlink',
            'seo service',
            'buy followers',
            'work from home',
            'earn money fast',
        ]);
        foreach ($this->normalizeValues($data) as $value) {
            if ($value === '') {
                continue;
            }

            foreach ($spamWords as $spamWord) {
                if ($spamWord !== '' && stripos($value, (string) $spamWord) !== false) {
                    $score += 45;
                    $reasons[] = 'spam_word';
                    break;
                }
            }

            $linkCount = preg_match_all('/https?:\/\/|www\./i', $value, $matches);
            if ($linkCount > $maxLinks) {
                $score += min(60, ($linkCount - $maxLinks) * 15);
                $reasons[] = 'too_many_links';
            }

            if (strlen($value) > 5000) {
                $score += 20;
                $reasons[] = 'very_long_payload';
            }

            if (preg_match('/(.)\1{14,}/', $value)) {
                $score += 20;
                $reasons[] = 'repeated_characters';
            }
        }

        $threshold = max(1, (int) apply_filters('madeit_forms_spam_threshold', 60));
        $isSpam = $score >= $threshold;

        return [
            'spam'      => $isSpam,
            'score'     => $score,
            'threshold' => $threshold,
            'reasons'   => array_values(array_unique($reasons)),
        ];
    }

    private function getIP()
    {
        if (is_callable($this->getIpCallback)) {
            return call_user_func($this->getIpCallback);
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
    }

    private function normalizeValues($data)
    {
        $values = [];

        if (!is_array($data)) {
            return $values;
        }

        $ignoredFields = apply_filters('madeit_forms_spam_ignored_fields', [
            'form_id',
            'action',
            'g-recaptcha-response',
            'madeit_form_rendered_at',
            'madeit_website',
        ]);

        foreach ($data as $key => $value) {
            if (in_array($key, $ignoredFields, true)) {
                continue;
            }

            if (is_array($value)) {
                $this->appendArrayValues($values, $value);
                continue;
            }

            $values[] = trim((string) $value);
        }

        return $values;
    }

    private function appendArrayValues(&$values, $value)
    {
        if (is_array($value)) {
            foreach ($value as $nestedValue) {
                $this->appendArrayValues($values, $nestedValue);
            }

            return;
        }

        $values[] = trim((string) $value);
    }
}
