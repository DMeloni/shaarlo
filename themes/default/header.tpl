<head>
    <title>Shaarlo - shaarli.fr</title>
    <meta charset="utf-8"/>
    <meta name="description" content="Site d'actualité alternatif par la communauté via shaarli." />
    <meta property="og:description" content="Site d'actualité alternatif par la communauté via shaarli." />
    <meta name="author" content="" />
    <meta name="viewport" content="width=device-width, user-scalable=yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

    <link rel="apple-touch-icon" href="favicon.png" />
    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="stylesheet" href="css/style.css?v=13" type="text/css" media="screen"/>
    
    {if="$dotsies=='yes'"}
        <link rel="stylesheet" href="css/dotsies.css" type="text/css" media="screen"/>
        <style>
        * {font-family: Dotsies;}
        </style>
    {/if}
    
    {if="$username"}
        <link rel="alternate" type="application/rss+xml" href="{$rss_url}?do=rss&amp;u={$username}" title="Shaarlo Feed" />
    {else}
        <link rel="alternate" type="application/rss+xml" href="{$rss_url}?do=rss" title="Shaarlo Feed" />
    {/if}
</head>
