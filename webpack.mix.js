const mix = require('laravel-mix');

mix.setPublicPath('public/')
    .postCss('resources/css/translator.css', 'css', [
        require('tailwindcss'),
    ])
    .js('resources/js/translator.js', 'js');

if (mix.inProduction()) {
    mix.version();
}