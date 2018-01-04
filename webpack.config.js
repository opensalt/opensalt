var Encore = require('@symfony/webpack-encore');
var path = require('path');

var vendorDir = './vendor';
var bowerDir = './vendor/bower-asset';
var npmDir = './vendor/npm-asset';
var assetsDir = './app/Resources/assets';
var buildDir = './web/build';

var sharedScripts = [
    bowerDir+'/html5-boilerplate/dist/js/plugins.js',
    bowerDir+'/jquery/dist/jquery.js',
    bowerDir+'/jquery-ui/jquery-ui.js',
    bowerDir+'/bootstrap-sass/assets/javascripts/bootstrap.js',
    bowerDir+'/fancytree/dist/jquery.fancytree-all.js',
    assetsDir+'/js/site.js'
];

/*
var apxScripts = [
    assetsDir+'/js/cftree/view-documentclass.js',
    assetsDir+'/js/cftree/view-trees.js',
    assetsDir+'/js/cftree/view-edit.js',
    assetsDir+'/js/cftree/view-modes.js',
    assetsDir+'/js/cftree/viewx.js',
    assetsDir+'/js/cftree/apxglobal.js'
];

var concat = require('concat-files');
concat(apxScripts, assetsDir+'/js/apx.js', function(err) {
    if (err) {
      throw err;
    }
    console.log('concat apx.js done');
});
*/

var mainScripts = [
    bowerDir+'/datatables.net/js/jquery.dataTables.js',
    bowerDir+'/datatables.net-bs/js/dataTables.bootstrap.js',
    bowerDir+'/datatables.net-fixedheader/js/dataTables.fixedHeader.js',
    bowerDir+'/datatables.net-scroller/js/dataTables.scroller.js',
    bowerDir+'/datatables.net-select/js/dataTables.select.js',
    //bowerDir+'/ui-contextmenu/jquery.ui-contextmenu.js',
    bowerDir+'/bootstrap-multiselect/dist/js/bootstrap-multiselect.js',
    bowerDir+'/select2/dist/js/select2.full.js',
    vendorDir+'/tetranz/select2entity-bundle/Tetranz/Select2EntityBundle/Resources/public/js/select2entity.js',
    bowerDir+'/twbs-pagination/jquery.twbsPagination.js',
    npmDir+'/bootstrap-notify/bootstrap-notify.min.js',
    npmDir+'/simplemde/dist/simplemde.min.js',
    assetsDir+'/js/application.js',
    assetsDir+'/js/lsdoc/index.js',
    npmDir+'/papaparse/papaparse.min.js',
    './build/js/apx.js'
];

Encore
    .setOutputPath('app/web/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()

    .addEntry('modernizr', bowerDir+'/html5-boilerplate/dist/js/vendor/modernizr-2.8.3.min.js')
    .addEntry('site', mainScripts)
    .addEntry('comments', [
        bowerDir+'/jquery-comments/js/jquery-comments.min.js',
        assetsDir+'/js/lsdoc/comments.js'
    ])

    .createSharedEntry('base', sharedScripts)

    .addStyleEntry('main', [
//        bowerDir+'/fancytree/dist/skin-lion/ui.fancytree.css',
//        vendorDir+'/mervick/material-design-icons/scss/material-icons.scss',
        assetsDir+'/sass/application.scss',
//        vendorDir+'/fortawesome/font-awesome/css/font-awesome.css'
    ])
    .addStyleEntry('commentscss', [
        bowerDir+'/jquery-comments/css/jquery-comments.css',
        assetsDir+'/sass/comments.scss'
    ])
    /*
    .enableSassLoader(function(sassOptions) {}, {
        resolveUrlLoader: true
    })
    */

    .autoProvideVariables({
        $: 'jquery',
        jQuery: 'jquery',
        'window.jQuery': 'jquery'
    })
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning()
    .configureBabel(function(babelConfig) {
        babelConfig.compact = true;
        babelConfig.presets.push([
            'env',
            {
                modules: false,
                targets: {
                    browsers: '> 1%',
                    uglify: true
                },
                useBuiltIns: true
            }
        ]);
    })
    .addLoader({
        test: /\.s[ac]ss$/,
        "use": [
            {
              "loader": "/build/node_modules/extract-text-webpack-plugin/dist/loader.js",
              "options": {
                "omit": 1,
                "remove": true
              }
            },
            {
              "loader": "style-loader?sourceMap"
            },
            {
              "loader": "css-loader",
              "options": {
                "minimize": false,
                "sourceMap": true,
                "importLoaders": 0
              }
            },
            {
              "loader": "resolve-url-loader",
              "options": {
                "sourceMap": true,
                "keepQuery": true,
                "root": __dirname+'/web'
                //,debug: true
              }
            },
            {
              "loader": "sass-loader",
              "options": {
                "sourceMap": true
              }
            }
        ],
        "exclude": /docker/
    })
;
/*
console.log(Encore.getWebpackConfig());
console.log(Encore.getWebpackConfig().module.rules);
console.log(JSON.stringify(Encore.getWebpackConfig().module.rules, false, 2));
*/

var config = Encore.getWebpackConfig();
config.context = __dirname;
config.resolve.alias = {
  'jquery': path.resolve(__dirname, bowerDir+'/jquery/dist/jquery.js'),
  'jquery-ui': path.resolve(__dirname, bowerDir+'/jquery-ui/jquery-ui.js'),
  'datatables.net': path.resolve(__dirname, bowerDir+'/datatables.net/js/jquery.dataTables.js'),
  'jquery-ui/ui/widgets/menu': path.resolve(__dirname, bowerDir+'/jquery-ui/ui/widgets/menu.js'),
  'simplemde': path.resolve(__dirname, npmDir+'/simplemde/dist/simplemde.min.js'),
  'papaparse': path.resolve(__dirname, npmDir+'/papaparse/papaparse.min.js'),
  'render-md': path.resolve(__dirname, assetsDir+'/js/cftree/render-md.js')
};
config.resolve.modules = [
  "node_modules",
  path.resolve(__dirname, "./web/assets/img")
  //path.resolve(__dirname, bowerDir)
];

module.exports = config;
