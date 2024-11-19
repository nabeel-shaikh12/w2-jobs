<script setup>
import { Head, usePage } from "@inertiajs/vue3";
const metaData = usePage().props?.seo ?? [];
const site_name = metaData?.title || metaData?.site_name || "";
const current_url = metaData?.current_url ?? window.location.href;
const tags = metaData?.tags || "";
const description = metaData?.description || "";
const preview = metaData?.preview || "";
</script>

<template>
  <Head>
    <title>{{ site_name }}</title>
    <meta name="description" itemprop="description" :content="description" />
    <meta name="keywords" :content="tags" />
    <meta property="og:description" :content="description" />
    <meta property="og:title" :content="site_name" />
    <meta property="og:url" :content="current_url" />

    <meta property="og:site_name" :content="site_name" />
    <meta property="og:image" :content="preview" />
    <meta property="og:image:url" :content="preview" />

    <meta name="twitter:card" :content="description" />
    <meta name="twitter:title" :content="site_name" />
    
    <script type="text/javascript">
    (function(c,l,a,r,i,t,y){
    c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
    t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
    y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "jpewr0o0fm");
    </script>
    
    <script> window._izq = window._izq || []; window._izq.push(["init"]); </script>
    <script src="https://cdn.izooto.com/scripts/e586b4c102818c24b84946a3a454a35b9a6bb123.js"></script>
    
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-SWWE4RJF5L"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    
    gtag('config', 'G-SWWE4RJF5L');
    </script>

  </Head>
</template>
