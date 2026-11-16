<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * SESI PER-TAB (multi akun dalam satu browser).
 *
 * Cookie session dibagi oleh semua tab pada satu browser, sehingga login akun B
 * menimpa akun A. Middleware ini memisahkan data login per "token tab":
 *
 *  - Tiap tab membawa token acak (`_t`) di URL / form / header X-Tab-Token.
 *  - Data login (id_user, id_role, dst) disimpan di cache dengan kunci
 *    session-id + token, bukan di session global.
 *  - Sebelum request diproses data tab dimuat ke session(); setelah selesai
 *    disimpan kembali dan dibersihkan dari session global.
 *
 * Kode lama yang memakai session('id_user') dkk TIDAK perlu diubah.
 */
class TabSession
{
    /** Key session yang bersifat "per tab" (data akun + alur lupa password). */
    public const KEYS = [
        'id_user', 'username', 'nama_lengkap', 'id_role', 'foto_profil',
        'fp_user_id', 'fp_email', 'fp_otp_hash', 'fp_otp_expires',
        'fp_attempts', 'fp_last_sent', 'fp_verified_until',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->resolveToken($request);

        // Tidak ada token: halaman biasa dibuka -> minta browser membuat token dulu.
        if (!$token) {
            if ($this->isPageNavigation($request)) {
                return $this->bootstrapResponse();
            }
            $request->session()->forget(self::KEYS);
            return $next($request);
        }

        $session = $request->session();
        $cacheKey = 'tabsess:' . sha1($session->getId() . '|' . $token);

        // Muat data akun milik tab ini
        $session->forget(self::KEYS);
        foreach ((array) Cache::get($cacheKey, []) as $k => $v) {
            $session->put($k, $v);
        }

        $response = $next($request);

        // Simpan kembali data akun tab ini, lalu bersihkan session global
        $data = [];
        foreach (self::KEYS as $k) {
            if ($session->has($k)) {
                $data[$k] = $session->get($k);
            }
        }
        if ($data) {
            Cache::put($cacheKey, $data, now()->addMinutes((int) config('session.lifetime', 120)));
        } else {
            Cache::forget($cacheKey);
        }
        $session->forget(self::KEYS);

        if ($response instanceof RedirectResponse) {
            $this->tagRedirect($request, $response, $token);
        } else {
            $this->injectScript($response, $token);
        }

        return $response;
    }

    private function resolveToken(Request $request): ?string
    {
        $candidates = [
            $request->header('X-Tab-Token'),
            $request->input('_t'),
        ];

        // Fallback: token dari halaman asal (Referer) bila request berasal dari tab ini
        $referer = $request->headers->get('referer');
        if ($referer) {
            $p = parse_url($referer);
            if (($p['host'] ?? null) === $request->getHost() && !empty($p['query'])) {
                parse_str($p['query'], $q);
                $candidates[] = $q['_t'] ?? null;
            }
        }

        foreach ($candidates as $c) {
            if (is_string($c) && preg_match('/^[a-f0-9]{16,64}$/', $c)) {
                return $c;
            }
        }
        return null;
    }

    private function isPageNavigation(Request $request): bool
    {
        return $request->isMethod('GET')
            && !$request->ajax()
            && !$request->is('storage/*', 'up')
            && str_contains((string) $request->header('Accept', ''), 'text/html');
    }

    /** Halaman kecil yang membuat/mengambil token tab (sessionStorage) lalu reload dengan ?_t= */
    private function bootstrapResponse(): Response
    {
        $html = '<!doctype html><meta charset="utf-8"><title>Memuat...</title>'
            . '<script>(function(){var k="scp_tab",t=null;'
            . 'try{t=sessionStorage.getItem(k);}catch(e){}'
            . 'if(!t||!/^[a-f0-9]{16,64}$/.test(t)){var a=new Uint8Array(16);(window.crypto||window.msCrypto).getRandomValues(a);'
            . 't="";for(var i=0;i<a.length;i++){t+=("0"+a[i].toString(16)).slice(-2);}'
            . 'try{sessionStorage.setItem(k,t);}catch(e){}}'
            . 'var u=new URL(location.href);u.searchParams.set("_t",t);location.replace(u.toString());})();</script>'
            . '<noscript>Aktifkan JavaScript untuk menggunakan aplikasi ini.</noscript>';

        return response($html, 200, [
            'Content-Type'  => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** Pastikan redirect internal tetap membawa token tab. */
    private function tagRedirect(Request $request, RedirectResponse $response, string $token): void
    {
        $url = $response->getTargetUrl();
        $p = parse_url($url);
        if (isset($p['host']) && $p['host'] !== $request->getHost()) {
            return; // redirect ke domain lain
        }
        if (isset($p['query']) && preg_match('/(^|&)_t=/', $p['query'])) {
            return;
        }
        $fragment = '';
        if (($pos = strpos($url, '#')) !== false) {
            $fragment = substr($url, $pos);
            $url = substr($url, 0, $pos);
        }
        $url .= (str_contains($url, '?') ? '&' : '?') . '_t=' . $token;
        $response->setTargetUrl($url . $fragment);
    }

    /** Sisipkan script ke setiap halaman HTML: menambah token ke link, form, fetch & XHR. */
    private function injectScript(Response $response, string $token): void
    {
        if (!$response instanceof HttpResponse) {
            return;
        }
        $type = strtolower((string) $response->headers->get('Content-Type', 'text/html'));
        if (!str_contains($type, 'text/html')) {
            return;
        }
        $content = $response->getContent();
        if (!is_string($content) || ($pos = strripos($content, '</body>')) === false) {
            return;
        }

        $js = <<<'JS'
<script>
(function(){
  var T='__TOKEN__';
  try{sessionStorage.setItem('scp_tab',T);}catch(e){}
  function same(u){try{var x=new URL(u,location.href);return x.origin===location.origin?x:null;}catch(e){return null;}}
  function tag(h){
    if(!h||/^(#|javascript:|mailto:|tel:|data:)/i.test(h))return h;
    var x=same(h);
    if(!x||x.searchParams.has('_t')||x.pathname.indexOf('/storage/')===0)return h;
    x.searchParams.set('_t',T);
    return x.pathname+x.search+x.hash;
  }
  function fix(){
    document.querySelectorAll('a[href]').forEach(function(a){
      var h=a.getAttribute('href'),n=tag(h);
      if(n!==h)a.setAttribute('href',n);
    });
    document.querySelectorAll('form').forEach(function(f){
      if(!f.querySelector('input[name="_t"]')){
        var i=document.createElement('input');
        i.type='hidden';i.name='_t';i.value=T;f.appendChild(i);
      }
    });
  }
  // Sembunyikan token dari address bar (token tetap disimpan di sessionStorage tab ini)
  try{
    var c=new URL(location.href);
    if(c.searchParams.has('_t')){
      c.searchParams.delete('_t');
      history.replaceState(history.state,'',c.pathname+c.search+c.hash);
    }
  }catch(e){}
  // fetch
  if(window.fetch){
    var of=window.fetch;
    window.fetch=function(input,init){
      try{
        var u=typeof input==='string'?input:(input&&input.url);
        if(u&&same(u)){
          init=init||{};
          var h=new Headers(init.headers||(input instanceof Request?input.headers:undefined));
          h.set('X-Tab-Token',T);init.headers=h;
        }
      }catch(e){}
      return of.call(this,input,init);
    };
  }
  // XMLHttpRequest
  var oo=XMLHttpRequest.prototype.open,os=XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.open=function(m,u){this.__scp=!!same(u);return oo.apply(this,arguments);};
  XMLHttpRequest.prototype.send=function(){
    if(this.__scp){try{this.setRequestHeader('X-Tab-Token',T);}catch(e){}}
    return os.apply(this,arguments);
  };
  fix();
  document.addEventListener('DOMContentLoaded',fix);
  var t=null;
  new MutationObserver(function(){clearTimeout(t);t=setTimeout(fix,50);})
    .observe(document.documentElement,{childList:true,subtree:true});
})();
</script>
JS;
        $js = str_replace('__TOKEN__', $token, $js);

        $response->setContent(substr($content, 0, $pos) . $js . substr($content, $pos));
    }
}
