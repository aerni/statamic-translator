const mix = require('laravel-mix');

mix.setPublicPath('resources/assets/')
    .postCss('src/resources/assets/css/translator.css', 'css', [
        require('tailwindcss'),
    ])
    .js('src/resources/assets/js/fieldtype.js', 'js');

if (mix.inProduction()) {
    mix.version();
}