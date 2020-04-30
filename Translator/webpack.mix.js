const mix = require('laravel-mix');
const path = require('path');
require('laravel-mix-purgecss');

mix.setPublicPath('resources/assets/')
    .postCss('resources/assets/css/src/translator.css', 'css', [
        require('tailwindcss'),
    ])
    .js('resources/assets/js/src/fieldtype.js', 'js');

if (mix.inProduction()) {
    mix.purgeCss({
        enabled: true,
        content: [
            path.join(__dirname, 'resources/assets/**/*.vue'),
        ],
        extensions: ['html', 'js', 'php', 'vue', 'svg', 'css', 'scss'],
        extractorPattern: /[\w-/.:]+(?<!:)/g
    })
    .version();
}