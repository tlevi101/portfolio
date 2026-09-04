<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Chrome binary
    |--------------------------------------------------------------------------
    |
    | The CV PDF is rendered by headless Chrome over the DevTools protocol, so
    | the template can use the same CSS the browser preview uses (flex-wrap,
    | border-radius, web fonts). Point this at the Chrome/Chromium executable.
    |
    | Ubuntu 24.04 only ships Chromium as a snap, which cannot run inside a
    | container, so the Sail image installs `google-chrome-stable` from Google's
    | apt repository. On Debian, `/usr/bin/chromium` works just as well.
    |
    */

    'chrome_binary' => env('CHROME_BINARY', '/usr/bin/google-chrome-stable'),

    /*
    |--------------------------------------------------------------------------
    | Chrome launch timeouts (milliseconds)
    |--------------------------------------------------------------------------
    */

    'chrome_timeout' => (int) env('CHROME_TIMEOUT', 30_000),

];
