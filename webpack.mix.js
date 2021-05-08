const mix = require('laravel-mix');

mix
  .js('resources/js/app.js', 'public/js/app.js')
  .js('resources/js/hljs.js', 'public/js/hljs.js')
  .js('resources/js/codemirror.js', 'public/js/codemirror.js')
  .sass('resources/sass/app.scss', 'public/css/app.css')
  .copyDirectory('resources/assets/img', 'public/img')
  .copyDirectory('resources/assets/img/exercises', 'public/img/exercises');

if (mix.inProduction()) {
  mix
    .version()
    // laravel-mix-make-file-hash не работает при пересборке ассетов в режиме watch
    .then(() => {
      const convertToFileHash = require("laravel-mix-make-file-hash");
      convertToFileHash({
        publicPath: "public",
        manifestFilePath: "public/mix-manifest.json"
      });
    });
}
