const mix = require('laravel-mix');

mix.setPublicPath('Translator/resources/assets/')
    .postCss('resources/assets/css/translator.css', 'css', [
        require('tailwindcss'),
    ])
    .js('resources/assets/js/fieldtype.js', 'js');