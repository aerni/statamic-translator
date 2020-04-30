const mix = require('laravel-mix');
const path = require('path');
require('laravel-mix-purgecss');

mix.setPublicPath('resources/assets/')
    .postCss('src/resources/assets/css/translator.css', 'css', [
        require('tailwindcss'),
    ])
    .js('src/resources/assets/js/fieldtype.js', 'js');

if (mix.inProduction()) {
    mix.purgeCss({
        enabled: true,
        content: [
            path.join(__dirname, 'src/resources/assets/**/*.vue'),
        ],
        extensions: ['html', 'js', 'php', 'vue', 'svg', 'css', 'scss'],
        extractorPattern: /[\w-/.:]+(?<!:)/g
    })
    .version();
}