const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

// Configure jQuery auto-loading for all modules
mix.autoload({
    jquery: ['$', 'window.jQuery', 'jQuery']
});

// Enable versioning for cache busting
mix.options({
    fileLoaderDirs: {
        images: 'images',
        fonts: 'fonts'
    },
    processCssUrls: true,
    clearConsole: true
});

mix.js('resources/js/app.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', [
        require('tailwindcss'),
        require('autoprefixer'),
    ])
    // Copy DPlayer and HLS.js to public directory
    .copy('node_modules/dplayer/dist/DPlayer.min.js', 'public/js/DPlayer.min.js')
    .copy('node_modules/hls.js/dist/hls.min.js', 'public/js/hls.min.js')
    // Add versioning for cache busting, especially for CDN
    .version()
    // Force generation of mix-manifest.json
    .webpackConfig({
        output: {
            publicPath: '/',
            chunkFilename: 'js/[name].[chunkhash].js'
        }
    });
