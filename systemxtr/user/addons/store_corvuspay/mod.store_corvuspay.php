<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Store_corvuspay
{
    /**
     * Relay action: receives a short-lived token, renders an auto-submitting
     * form that POSTs the signed payload to CorvusPay checkout.
     * Called via ?ACT=xxx&t=TOKEN
     */
    public function relay()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        // Never cache the relay page
        ee()->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        ee()->output->set_header('Pragma: no-cache');
        ee()->output->set_header('Expires: 0');

        $token = ee()->input->get('t');
        $store = $_SESSION['corvus_relay'] ?? [];

        if (!$token || empty($store[$token])) {
            ee()->output->set_status_header(400);
            ee()->output->set_header('Content-Type: text/plain; charset=UTF-8');
            ee()->output->set_output('Missing or expired CorvusPay payload.');
            return;
        }

        $rec  = $store[$token];
        $url  = $rec['url']  ?? null;
        $data = $rec['data'] ?? null;

        // One-time use: remove the token immediately
        unset($_SESSION['corvus_relay'][$token]);

        // Also purge any other expired tokens
        $now = time();
        foreach ($_SESSION['corvus_relay'] as $tok => $r) {
            if (!isset($r['ts']) || $r['ts'] < ($now - 300)) {
                unset($_SESSION['corvus_relay'][$tok]);
            }
        }

        if (empty($url) || empty($data) || !is_array($data)) {
            ee()->output->set_status_header(400);
            ee()->output->set_header('Content-Type: text/plain; charset=UTF-8');
            ee()->output->set_output('Invalid CorvusPay payload.');
            return;
        }

        $h = static function ($v) {
            return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        };

        $fields = '';
        foreach ($data as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $fields .= '<input type="hidden" name="' . $h($k) . '" value="' . $h($v) . '">' . "\n";
        }

        $html = <<<HTML
<!doctype html>
<html lang="hr">
<head>
  <meta charset="utf-8">
  <title>Preusmjeravanje na CorvusPay…</title>
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="robots" content="noindex,nofollow">
</head>
<body>
  <p>Preusmjeravanje na CorvusPay… Ako se ne nastavi automatski, kliknite gumb.</p>
  <form id="corvus_f" action="{$h($url)}" method="post" accept-charset="UTF-8">
    {$fields}
    <button type="submit" id="go">Nastavi na plaćanje</button>
  </form>

  <script>
  (function () {
    var f = document.getElementById('corvus_f');
    if (!f) return;
    var submitted = false;
    function go() {
      if (submitted) return;
      submitted = true;
      try {
        if (typeof f.requestSubmit === 'function') { f.requestSubmit(); return; }
      } catch (e) {}
      f.submit();
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', go, { once: true });
    } else {
      go();
    }
    setTimeout(go, 300);
    setTimeout(go, 1200);
  })();
  </script>

  <noscript>
    <p>JavaScript je isključen. Kliknite "Nastavi na plaćanje" za nastavak.</p>
  </noscript>
</body>
</html>
HTML;

        ee()->output->set_header('Content-Type: text/html; charset=UTF-8');
        ee()->output->set_output($html);
    }
}
