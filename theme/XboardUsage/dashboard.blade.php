<!doctype html>
<html lang="zh-CN">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" />
  <title>{{$title}}</title>
  <link rel="stylesheet" href="/theme/{{$theme}}/assets/main.css" />
</head>

<body>
  <script>
    window.routerBase = "/";
    window.settings = {
      title: '{{$title}}',
      assets_path: '/theme/{{$theme}}/assets',
      theme: {
        color: '{{ $theme_config['theme_color'] ?? "default" }}',
      },
      version: '{{$version}}',
      background_url: '{{$theme_config['background_url'] ?? ''}}',
      description: '{{$description}}',
      logo: '{{$logo}}',
      billing_mode: '{{ admin_setting("billing_mode", "subscription") }}',
      usage_price_per_gb: {{ (int)admin_setting("usage_price_per_gb", 100) }},
      secure_path: '{{ admin_setting("secure_path", admin_setting("frontend_admin_path", hash("crc32b", config("app.key")))) }}'
    }
  </script>
  <div id="app"></div>
  <script type="module" src="/theme/{{$theme}}/assets/app.js"></script>
  {!! $theme_config['custom_html'] ?? '' !!}
</body>

</html>
